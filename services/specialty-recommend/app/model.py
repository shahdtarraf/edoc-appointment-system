from __future__ import annotations

import json
from pathlib import Path

import joblib
import numpy as np
import scipy.sparse as sp

from .abstention import decide_abstention
from .preprocess import preprocess_tfidf_conservative

ARTIFACT_DIR = Path(__file__).resolve().parents[1] / "artifacts"

ALLOWED_IDS: frozenset[int] | None = None
ID_TO_NAME: dict[int, str] = {}
WORD_VEC = None
CHAR_VEC = None
CLF = None
RULES: dict = {}
METADATA: dict = {}
LOADED = False
LOAD_ERROR: str | None = None


def _load_json(path: Path) -> dict:
    with path.open(encoding="utf-8") as handle:
        return json.load(handle)


def load_artifacts() -> None:
    global ALLOWED_IDS, ID_TO_NAME, WORD_VEC, CHAR_VEC, CLF, RULES, METADATA, LOADED, LOAD_ERROR
    try:
        mapping = _load_json(ARTIFACT_DIR / "model_class_mapping.json")
        ID_TO_NAME = {int(k): v for k, v in mapping["id_to_name"].items()}
        ALLOWED_IDS = frozenset(ID_TO_NAME)
        if len(ID_TO_NAME) != 30:
            raise ValueError(f"Expected 30 specialties, got {len(ID_TO_NAME)}")

        METADATA = _load_json(ARTIFACT_DIR / "model_metadata.json")
        RULES = _load_json(ARTIFACT_DIR / "abstention_rules.json")

        WORD_VEC = joblib.load(ARTIFACT_DIR / "tfidf_word_vectorizer.joblib")
        CHAR_VEC = joblib.load(ARTIFACT_DIR / "tfidf_char_vectorizer.joblib")
        CLF = joblib.load(ARTIFACT_DIR / "best_model.joblib")

        class_ids = {int(c) for c in CLF.classes_}
        if class_ids != set(ID_TO_NAME):
            raise ValueError("Model classes do not match model_class_mapping.json")
        LOADED = True
        LOAD_ERROR = None
    except Exception as exc:
        LOADED = False
        LOAD_ERROR = type(exc).__name__
        raise


def health_payload() -> dict:
    return {
        "ok": bool(LOADED),
        "model_loaded": bool(LOADED),
        "n_specialties": len(ID_TO_NAME),
        "specialty_ids": sorted(ID_TO_NAME),
        "selected_model": METADATA.get("selected_model_name"),
        "production_approved": False,
        "clinical_approved": False,
        "error": LOAD_ERROR,
    }


def recommend(raw_text: str) -> dict:
    if not LOADED:
        return {
            "status": "error",
            "message": "Recommendation service is not ready.",
            "recommendations": [],
            "disclaimer": RULES.get("disclaimer", ""),
        }

    max_chars = int(RULES["input_guards"]["max_input_chars"])
    if raw_text is None or not str(raw_text).strip():
        return _abstain("empty_input")
    text = str(raw_text)
    if len(text) > max_chars:
        return _abstain("input_too_long")

    processed = preprocess_tfidf_conservative(text)
    Xw = WORD_VEC.transform([processed])
    Xc = CHAR_VEC.transform([processed])
    X = sp.hstack([Xw, Xc], format="csr", dtype=np.float32)
    scores = CLF.decision_function(X)[0]
    classes = CLF.classes_
    order = np.argsort(scores)[::-1]
    top3 = []
    for idx in order[:3]:
        sid = int(classes[idx])
        if sid not in ALLOWED_IDS:
            continue
        top3.append(
            {
                "specialty_id": sid,
                "specialty_name": ID_TO_NAME[sid],
            }
        )
    if len(top3) < 1:
        return _abstain("out_of_scope")

    top1_score = float(scores[order[0]])
    margin = float(scores[order[0]] - scores[order[1]]) if len(order) > 1 else 0.0
    abstain, reason = decide_abstention(processed, top1_score, margin, RULES)
    if abstain:
        return _abstain(reason)

    return {
        "status": "recommend",
        "message": "Experimental specialty suggestions only. Not a diagnosis.",
        "recommendations": top3,
        "disclaimer": RULES["disclaimer"],
        "n_supported_specialties": 30,
    }


def _abstain(reason: str) -> dict:
    return {
        "status": "abstain",
        "message": RULES.get(
            "user_message_abstain",
            "لم نتمكن من تحديد التخصص المناسب من وصفك. يمكنك اختيار التخصص يدوياً أو مراجعة الطبيب لتوجيهك.",
        ),
        "recommendations": [],
        "disclaimer": RULES.get("disclaimer", ""),
        "abstain_reason": reason,
    }
