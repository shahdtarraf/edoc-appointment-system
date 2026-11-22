"""CPU local tests. Do not treat this as clinical validation."""

from __future__ import annotations

import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))

from app import model  # noqa: E402
from app.preprocess import preprocess_tfidf_conservative  # noqa: E402

EXPECTED_PREPROCESS = {
    "ألم إلتهاب آلام ٱلقلب": "الم التهاب الام القلب",
    "صُدَاعٌ شَـدِيـــدٌ": "صداع شديد",
    "حُمّى وغدة درقية ورؤية سيئة ومستشفى": "حمى وغدة درقية ورؤية سيئة ومستشفى",
}

SYNTHETIC_RECOMMEND = {
    "أعاني من حكة شديدة واحمرار وطفح جلدي على اليدين منذ أسبوع": 13,
    "لدي تشوش في الرؤية واحمرار وحرقة في العين اليمنى": 34,
    "أشعر بخفقان سريع في القلب وضيق تنفس عند صعود الدرج": 5,
    "لدي ألم شديد في الضرس مع تورم في اللثة وحساسية للماء البارد": 11,
}

ABSTAIN_CASES = [
    ("", "empty_input"),
    ("   ", "empty_input"),
    ("أ", "too_few_tokens"),
    ("تعب عام", "generic_phrase"),
    ("تعب", "too_few_tokens"),
    ("ألم", "too_few_tokens"),
        ("asdf qwer zxcv", "no_arabic_content"),
        ("@@@ ###", "no_arabic_content"),
    (
        "ألم أسنان وطفح جلدي وضعف نظر وألم صدر معاً",
        "multiple_body_systems",
    ),
]


def main() -> int:
    failures = []

    for raw, expected in EXPECTED_PREPROCESS.items():
        got = preprocess_tfidf_conservative(raw)
        if got != expected:
            failures.append(f"preprocess {raw!r}: {got!r} != {expected!r}")

    model.load_artifacts()
    if not model.LOADED:
        print("FAIL: model did not load")
        return 1

    ids = sorted(model.ID_TO_NAME)
    if len(ids) != 30:
        failures.append(f"expected 30 ids, got {len(ids)}")

    mapping_path = ROOT / "artifacts" / "model_class_mapping.json"
    mapping = json.loads(mapping_path.read_text(encoding="utf-8"))
    mapped_ids = sorted(int(k) for k in mapping["id_to_name"])
    if ids != mapped_ids:
        failures.append("loaded ids != mapping file")

    health = model.health_payload()
    if not health["ok"] or health["n_specialties"] != 30:
        failures.append(f"health payload bad: {health}")

    model.load_artifacts()
    for text, expected_id in SYNTHETIC_RECOMMEND.items():
        first = model.recommend(text)
        second = model.recommend(text)
        if first != second:
            failures.append(f"reload/repeat mismatch for {expected_id}")
        if first.get("status") != "recommend":
            failures.append(f"expected recommend for {expected_id}, got {first}")
            continue
        top_id = first["recommendations"][0]["specialty_id"]
        if top_id != expected_id:
            failures.append(f"top1 {top_id} != {expected_id} for case")
        rec_ids = {r["specialty_id"] for r in first["recommendations"]}
        if not rec_ids.issubset(set(model.ID_TO_NAME)):
            failures.append(f"out of scope ids: {rec_ids}")
        if any("score" in r or "prob" in r for r in first["recommendations"]):
            failures.append("scores leaked into public recommendations")

    for text, reason in ABSTAIN_CASES:
        result = model.recommend(text)
        if result.get("status") != "abstain":
            failures.append(f"expected abstain ({reason}) got {result.get('status')} for {text!r}")
        elif result.get("abstain_reason") != reason:
            failures.append(
                f"abstain reason {result.get('abstain_reason')!r} != {reason!r} for {text!r}"
            )

    long_text = "ألم في الضرس " * 400
    long_result = model.recommend(long_text)
    if long_result.get("status") != "abstain" or long_result.get("abstain_reason") != "input_too_long":
        failures.append(f"long input: {long_result}")

    if failures:
        print("FAILED")
        for item in failures:
            print(" -", item)
        return 1
    print("PASS: model load, health, recommend, reload stability, 30-class scope, abstain guards")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
