"""HTTP tests against a running local uvicorn process."""

from __future__ import annotations

import json
import os
import urllib.error
import urllib.request

BASE = "http://127.0.0.1:8081"
TOKEN = os.environ.get("AI_SERVICE_TOKEN", "local-dev-token")


def get(path: str) -> tuple[int, dict]:
    with urllib.request.urlopen(BASE + path, timeout=30) as resp:
        return resp.status, json.loads(resp.read().decode("utf-8"))


def post(path: str, payload: dict, token: str | None = TOKEN) -> tuple[int, dict]:
    data = json.dumps(payload, ensure_ascii=False).encode("utf-8")
    headers = {"Content-Type": "application/json; charset=utf-8"}
    if token:
        headers["X-AI-Service-Token"] = token
    req = urllib.request.Request(
        BASE + path,
        data=data,
        headers=headers,
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            return resp.status, json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as exc:
        body = json.loads(exc.read().decode("utf-8")) if exc.fp else {}
        return exc.code, body


def main() -> int:
    status, health = get("/health")
    assert status == 200, status
    assert health.get("ok") is True
    assert health.get("n_specialties") == 30

    status, rec = post(
        "/recommend",
        {"text": "لدي ألم شديد في الضرس مع تورم في اللثة وحساسية للماء البارد"},
    )
    assert status == 200, rec
    assert rec["status"] == "recommend"
    assert rec["recommendations"][0]["specialty_id"] == 11
    assert "decision_score" not in rec["recommendations"][0]

    status, abstain = post("/recommend", {"text": "تعب عام"})
    assert status == 200
    assert abstain["status"] == "abstain"

    status, empty = post("/recommend", {"text": ""})
    assert empty["status"] == "abstain"

    status, denied = post("/recommend", {"text": "لدي ألم في الضرس"}, token="")
    assert status == 401

    print("PASS: HTTP /health and /recommend")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
