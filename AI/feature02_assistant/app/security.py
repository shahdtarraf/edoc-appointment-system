from __future__ import annotations

import os
import time
from collections import defaultdict, deque

from fastapi import Request
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.responses import JSONResponse

MAX_BODY_BYTES = 4096
RATE_LIMIT_WINDOW_SEC = 60
RATE_LIMIT_MAX = 40
_hits: dict[str, deque[float]] = defaultdict(deque)


def required_token() -> str:
    return (os.environ.get("ASSISTANT_SERVICE_TOKEN") or "").strip()


def extract_token(request: Request) -> str:
    header = request.headers.get("x-assistant-token", "")
    if header.strip():
        return header.strip()
    auth = request.headers.get("authorization", "")
    if auth.lower().startswith("bearer "):
        return auth[7:].strip()
    return ""


class AssistantGuard(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next):
        if request.url.path == "/health":
            return await call_next(request)
        token = required_token()
        if token and extract_token(request) != token:
            return JSONResponse({"detail": "Unauthorized"}, status_code=401)
        length = request.headers.get("content-length")
        if length and length.isdigit() and int(length) > MAX_BODY_BYTES:
            return JSONResponse({"detail": "Payload too large"}, status_code=413)
        ip = request.client.host if request.client else "unknown"
        now = time.time()
        q = _hits[ip]
        while q and now - q[0] > RATE_LIMIT_WINDOW_SEC:
            q.popleft()
        if len(q) >= RATE_LIMIT_MAX:
            return JSONResponse({"detail": "Too many requests"}, status_code=429)
        q.append(now)
        return await call_next(request)
