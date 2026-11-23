"""Abstention: Validation LinearSVC margins plus conservative input guards.

Thresholds are NOT fitted on Test. Decision scores are not probabilities.
"""

from __future__ import annotations

from typing import Any


def decide_abstention(
    processed: str,
    top1_score: float,
    margin: float,
    rules: dict[str, Any],
) -> tuple[bool, str]:
    guards = rules["input_guards"]
    tokens = [t for t in processed.split() if t]
    arabic_letters = sum(1 for ch in processed if "\u0600" <= ch <= "\u06FF")

    if not processed or not tokens:
        return True, "empty_after_preprocess"
    if len(tokens) < int(guards["min_tokens"]):
        return True, "too_few_tokens"
    if arabic_letters < int(guards["min_arabic_letters"]):
        return True, "no_arabic_content"
    if processed in set(guards["generic_phrases_after_preprocess"]):
        return True, "generic_phrase"

    systems_hit = 0
    for keywords in guards["body_system_keywords"].values():
        if any(kw in processed for kw in keywords):
            systems_hit += 1
    if systems_hit >= int(guards["multi_system_min_distinct"]):
        return True, "multiple_body_systems"

    if margin < float(rules["validation_thresholds"]["abstain_if_margin_lt"]):
        return True, "validation_low_margin"
    if top1_score < float(rules["validation_thresholds"]["abstain_if_top1_lt"]):
        return True, "validation_low_top1"
    return False, ""
