from __future__ import annotations

import re

from .retrieve import normalize

DIALECT = (
    ("بدي", "اريد"),
    ("وين", "اين"),
    ("شلون", "كيف"),
    ("شلونك", "كيف"),
    ("شو", "ما"),
    ("ليش", "لماذا"),
    ("هلق", "الان"),
    ("هيك", "هكذا"),
    ("منيح", "جيد"),
    ("دكتور", "طبيب"),
    ("دكاتره", "اطباء"),
    ("بلاقي", "اجد"),
    ("تلاقي", "تجد"),
    ("اعمل", "انشئ"),
    ("سجل دخول", "تسجيل الدخول"),
    ("لوجن", "تسجيل الدخول"),
    ("اكاونت", "حساب"),
    ("موبايل", "هاتف"),
    ("ابلكيشن", "منصه"),
    ("book", "حجز"),
    ("login", "دخول"),
)

FOLLOW_RE = re.compile(
    r"^(كيف|كيف\؟|وين|وين\؟|ليش|اشرح|اشرحلي|اكتر|المزيد|and then|how\??|where\??|why\??|more|explain)$",
    re.IGNORECASE,
)


def expand_dialect(text: str) -> str:
    n = normalize(text)
    extras = []
    for src, dst in DIALECT:
        if normalize(src) in n and normalize(dst) not in n:
            extras.append(dst)
    if not extras:
        return text
    return text + " " + " ".join(extras)


def is_followup(text: str) -> bool:
    t = (text or "").strip()
    if len(t) > 48:
        return False
    compact = re.sub(r"\s+", " ", t)
    return bool(FOLLOW_RE.match(compact)) or compact in (
        "وكيف",
        "وبعدين",
        "شو بعدا",
        "وين بلاقيها",
        "وين بلاقيه",
        "كيف استخدمها",
        "tell me more",
        "what next",
    )


def last_user_text(history: list | None) -> str:
    if not history:
        return ""
    for item in reversed(history):
        if not isinstance(item, dict):
            continue
        if (item.get("role") or "") == "user":
            return str(item.get("text") or "")[:400]
    return ""


def resolve_context(message: str, history: list | None) -> str:
    text = (message or "").strip()
    if not is_followup(text):
        return text
    prev = last_user_text(history)
    if not prev:
        return text
    return (prev + " " + text).strip()


def split_parts(message: str) -> list[str]:
    text = (message or "").strip()
    if not text:
        return []
    chunks = re.split(r"(?<=[؟?])\s+", text)
    parts = [c.strip() for c in chunks if c.strip()]
    if len(parts) <= 1:
        return [text]
    return parts[:2]
