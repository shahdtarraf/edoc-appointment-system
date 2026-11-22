"""Shared-token gate, request size limit, and a light per-IP rate limit.

The shared token must come from the environment, never from Git.
/health stays unauthenticated so Render can probe liveness.
"""

from __future__ import annotations

import os
import time
from collections import defaultdict, deque

from fastapi import Request
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.responses import JSONResponse

MAX_BODY_BYTES = 8192
RATE_LIMIT_WINDOW_SEC = 60
RATE_LIMIT_MAX = 30
_hits: dict[str, deque[float]] = defaultdict(deque)


def required_token() -> str:
    return (os.environ.get("AI_SERVICE_TOKEN") or "").strip()


def token_configured() -> bool:
    return required_token() != ""


def extract_token(request: Request) -> str:
    header = request.headers.get("x-ai-service-token", "")
    if header.strip():
        return header.strip()
    auth = request.headers.get("authorization", "")
    if auth.lower().startswith("bearer "):
        return auth[7:].strip()
    return ""


def tokens_match(provided: str) -> bool:
    expected = required_token()
    if expected == "" or provided == "":
        return False
    if len(expected) != len(provided):
        return False
    result = 0
    for left, right in zip(expected.encode("utf-8"), provided.encode("utf-8")):
        result |= left ^ right
    return result == 0


def rate_limited(ip: str) -> bool:
    now = time.time()
    bucket = _hits[ip]
    while bucket and now - bucket[0] > RATE_LIMIT_WINDOW_SEC:
        bucket.popleft()
    if len(bucket) >= RATE_LIMIT_MAX:
        return True
    bucket.append(now)
    return False


class RecommendGuardMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next):
        path = request.url.path
        if path != "/recommend":
            return await call_next(request)

        length = request.headers.get("content-length")
        if length is not None:
            try:
                if int(length) > MAX_BODY_BYTES:
                    return JSONResponse({"detail": "Request too large"}, status_code=413)
            except ValueError:
                return JSONResponse({"detail": "Invalid content length"}, status_code=400)

        if not token_configured():
            return JSONResponse({"detail": "Recommendation service is not configured"}, status_code=503)
        if not tokens_match(extract_token(request)):
            return JSONResponse({"detail": "Unauthorized"}, status_code=401)

        client_ip = request.client.host if request.client else "unknown"
        if rate_limited(client_ip):
            return JSONResponse({"detail": "Too many requests"}, status_code=429)

        return await call_next(request)
