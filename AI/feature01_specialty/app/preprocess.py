"""Stage 04 Path B: question_tfidf_conservative (training text)."""

from __future__ import annotations

import re
import unicodedata

_INVISIBLE_CTRL_RE = re.compile(
    r"[\x00-\x08\x0b\x0c\x0e-\x1f\x7f\u200B-\u200F\u202A-\u202E\u2060-\u206F\uFEFF]"
)
_SPACE_RE = re.compile(r"\s+")
_DIACRITICS_RE = re.compile(r"[\u0610-\u061A\u064B-\u065F\u0670\u06D6-\u06ED]")
_TATWEEL_RE = re.compile(r"\u0640+")
_DIGIT_TRANS = str.maketrans("٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹", "01234567890123456789")
_ALEF_TRANS = str.maketrans("أإآٱ", "اااا")
_REDACTED_RE = re.compile(
    r"\[(?:REDACTED_(?:PHONE|EMAIL|URL|ID|PERSONAL_INFO)|QUARANTINED_FIELD)\]"
)


def _protect_redacted_lower(s: str) -> str:
    if "[" not in s:
        return s.lower()
    parts = []
    last = 0
    for m in _REDACTED_RE.finditer(s):
        parts.append(s[last : m.start()].lower())
        parts.append(m.group(0))
        last = m.end()
    parts.append(s[last:].lower())
    return "".join(parts)


def preprocess_tfidf_conservative(val) -> str:
    if val is None:
        return ""
    s = unicodedata.normalize("NFC", str(val))
    s = _INVISIBLE_CTRL_RE.sub("", s)
    s = _TATWEEL_RE.sub("", s)
    s = _DIACRITICS_RE.sub("", s)
    s = s.translate(_ALEF_TRANS)
    s = s.translate(_DIGIT_TRANS)
    s = _protect_redacted_lower(s)
    s = _SPACE_RE.sub(" ", s).strip()
    return s
