from __future__ import annotations

import json
from pathlib import Path

KNOWLEDGE_DIR = Path(__file__).resolve().parents[1] / "knowledge_base"


def load_documents() -> list[dict]:
    docs: list[dict] = []
    if not KNOWLEDGE_DIR.is_dir():
        return docs
    for path in sorted(KNOWLEDGE_DIR.glob("*.json")):
        raw = json.loads(path.read_text(encoding="utf-8"))
        for item in raw.get("documents", []):
            item = dict(item)
            item["file"] = path.name
            docs.append(item)
    return docs
