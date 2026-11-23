from __future__ import annotations

import json
import os
import urllib.error
import urllib.request


def maybe_rewrite(question: str, context: str, lang: str) -> str | None:
    url = (os.environ.get("ASSISTANT_LLM_URL") or "").strip()
    token = (os.environ.get("ASSISTANT_LLM_TOKEN") or "").strip()
    if not url or not token:
        return None
    q = question or ""
    if "@" in q or "password" in q.lower() or "كلمة مرور" in q or "token" in q.lower():
        return None
    model = (os.environ.get("ASSISTANT_LLM_MODEL") or "local").strip()
    system = (
        "Reply in the same language as the question. Use only the CONTEXT. "
        "Do not invent doctors, bookings, specialties, or medical advice. "
        "If CONTEXT is not enough, say the information is unavailable. Keep it short."
    )
    payload = {
        "model": model,
        "temperature": 0,
        "messages": [
            {"role": "system", "content": system},
            {"role": "user", "content": "QUESTION:\n" + question[:500] + "\n\nCONTEXT:\n" + context[:1800]},
        ],
    }
    data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        url,
        data=data,
        headers={
            "Content-Type": "application/json",
            "Authorization": "Bearer " + token,
        },
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=8) as resp:
            body = json.loads(resp.read().decode("utf-8"))
        msg = body.get("choices", [{}])[0].get("message", {}).get("content")
        if isinstance(msg, str) and msg.strip():
            return msg.strip()
    except Exception:
        return None
    return None
