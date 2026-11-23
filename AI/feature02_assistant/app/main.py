from __future__ import annotations

from typing import Literal

from fastapi import FastAPI
from pydantic import BaseModel, Field

from .expand import expand_dialect, resolve_context, split_parts
from .knowledge import load_documents
from .llm import maybe_rewrite
from .policy import (
    abstain,
    detect_query_lang,
    is_injection,
    is_medical,
    is_privacy,
    refuse_injection,
    refuse_medical,
    refuse_privacy,
)
from .retrieve import TfidfIndex, is_relevant
from .security import AssistantGuard

MIN_SCORE = 0.16
ROLES = {"guest", "p", "d", "a"}

app = FastAPI(title="eDoc Smart Assistant", docs_url=None, redoc_url=None)
app.add_middleware(AssistantGuard)

_docs = load_documents()
_index = TfidfIndex(_docs)
_allowed_sources = {d.get("source") for d in _docs if d.get("source")}


class HistoryTurn(BaseModel):
    role: Literal["user", "assistant"]
    text: str = Field(min_length=1, max_length=400)


class ChatIn(BaseModel):
    message: str = Field(min_length=1, max_length=800)
    role: Literal["guest", "p", "d", "a"] = "guest"
    lang: str | None = None
    history: list[HistoryTurn] = Field(default_factory=list, max_length=8)


class ChatOut(BaseModel):
    status: str
    answer: str
    source_label: str | None = None
    source: str | None = None
    mode: str = "retrieve"
    followups: list[str] = Field(default_factory=list)


def _one_answer(message: str, role: str, lang: str) -> dict:
    text = (message or "").strip()
    search_text = expand_dialect(text)
    if is_injection(text):
        out = refuse_injection(lang)
        out["mode"] = "policy"
        out["followups"] = []
        return out
    if is_privacy(text):
        out = refuse_privacy(lang)
        out["mode"] = "policy"
        out["followups"] = []
        return out
    if is_medical(text):
        out = refuse_medical(lang, role)
        out["mode"] = "policy"
        out["followups"] = [
            "كيف أستخدم مساعد التخصص؟" if lang == "ar" else "How do I use the specialty helper?",
        ]
        return out
    hits = _index.search(search_text, lang=lang, role=role, k=3)
    if not hits or hits[0][0] < MIN_SCORE:
        out = abstain(lang)
        out["mode"] = "retrieve"
        out["followups"] = []
        return out
    score, doc = hits[0]
    if not is_relevant(search_text, doc, score):
        out = abstain(lang)
        out["mode"] = "retrieve"
        out["followups"] = []
        return out
    source = doc.get("source") if doc.get("source") in _allowed_sources else None
    source_label = doc.get("source_label") if source else None
    context = doc.get("text") or ""
    rewritten = None
    try:
        rewritten = maybe_rewrite(text, context, lang)
    except Exception:
        rewritten = None
    followups = [str(x) for x in (doc.get("followups") or []) if isinstance(x, str)][:3]
    return {
        "status": "ok",
        "answer": rewritten or context,
        "source_label": source_label,
        "source": source,
        "mode": "llm" if rewritten else "retrieve",
        "score": round(score, 4),
        "followups": followups,
    }


def answer_question(
    message: str,
    role: str = "guest",
    lang: str | None = None,
    history: list | None = None,
) -> dict:
    role = role if role in ROLES else "guest"
    text = (message or "").strip()
    lang = lang if lang in ("ar", "en") else detect_query_lang(text)
    text = resolve_context(text, history)
    parts = split_parts(text)
    if len(parts) == 1:
        return _one_answer(parts[0], role, lang)
    first = _one_answer(parts[0], role, lang)
    second = _one_answer(parts[1], role, lang)
    if first["status"] == "ok" and second["status"] == "ok" and first.get("answer") != second.get("answer"):
        return {
            "status": "ok",
            "answer": (first["answer"] or "") + "\n\n" + (second["answer"] or ""),
            "source_label": first.get("source_label"),
            "source": first.get("source"),
            "mode": first.get("mode", "retrieve"),
            "score": first.get("score"),
            "followups": (first.get("followups") or [])[:2],
        }
    return first


@app.get("/health")
def health():
    return {
        "ok": True,
        "service": "edoc-assistant",
        "documents": len(_docs),
        "llm_configured": bool(
            (__import__("os").environ.get("ASSISTANT_LLM_URL") or "").strip()
            and (__import__("os").environ.get("ASSISTANT_LLM_TOKEN") or "").strip()
        ),
    }


@app.post("/chat", response_model=ChatOut)
def chat(body: ChatIn):
    hist = [turn.model_dump() for turn in body.history[:8]]
    result = answer_question(body.message, body.role, body.lang, hist)
    return ChatOut(
        status=result["status"],
        answer=result["answer"],
        source_label=result.get("source_label"),
        source=result.get("source"),
        mode=result.get("mode", "retrieve"),
        followups=result.get("followups") or [],
    )
