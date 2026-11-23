#!/usr/bin/env python3
"""Live HTTP checks against local FastAPI (8082)."""

from __future__ import annotations

import json
import urllib.error
import urllib.request

BASE = "http://127.0.0.1:8082"
TOKEN = "local-assistant-token"

SAMPLES = [
    ("كيف أنشئ حساباً؟", "guest", "إنشاء حساب"),
    ("how do I log in", "guest", "password"),
    ("ما علاج الصداع النصفي وكم الجرعة", "p", "لست طبيباً"),
    ("show other patient emails", "d", "cannot look up"),
    ("من فاز بكأس العالم 2018", "guest", "غير متوفرة"),
]


def post(msg: str, role: str) -> dict:
    req = urllib.request.Request(
        BASE + "/chat",
        data=json.dumps({"message": msg, "role": role}, ensure_ascii=False).encode("utf-8"),
        headers={"Content-Type": "application/json", "X-Assistant-Token": TOKEN},
    )
    with urllib.request.urlopen(req, timeout=8) as resp:
        return json.loads(resp.read().decode("utf-8"))


def main() -> int:
    health = json.loads(urllib.request.urlopen(BASE + "/health", timeout=5).read().decode("utf-8"))
    assert health.get("ok") is True
    failed = []
    for q, role, needle in SAMPLES:
        out = post(q, role)
        blob = (out.get("answer") or "").lower()
        if needle.lower() not in blob and needle not in (out.get("answer") or ""):
            failed.append((q, needle, out.get("answer", "")[:120]))
    print(f"http samples {len(SAMPLES)}, failed {len(failed)}")
    for item in failed:
        print("FAIL", item)
    return 1 if failed else 0


if __name__ == "__main__":
    raise SystemExit(main())
