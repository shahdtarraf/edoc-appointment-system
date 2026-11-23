from __future__ import annotations

import math
import re
import unicodedata
from collections import Counter

ARABIC_RE = re.compile(r"[\u0600-\u06FF]")


def detect_lang(text: str) -> str:
    ar = len(ARABIC_RE.findall(text or ""))
    latin = len(re.findall(r"[A-Za-z]", text or ""))
    if ar >= latin and ar > 0:
        return "ar"
    if latin > 0:
        return "en"
    return "ar"


def normalize(text: str) -> str:
    text = unicodedata.normalize("NFKC", text or "")
    text = text.replace("أ", "ا").replace("إ", "ا").replace("آ", "ا")
    text = text.replace("ة", "ه").replace("ى", "ي")
    text = text.lower()
    return re.sub(r"[^\w\u0600-\u06FF]+", " ", text, flags=re.UNICODE).strip()


def features(text: str) -> list[str]:
    norm = normalize(text)
    packed = re.sub(r"\s+", "", norm)
    grams = []
    for n in (3, 4):
        if len(packed) >= n:
            grams.extend(packed[i : i + n] for i in range(len(packed) - n + 1))
    tokens = [t for t in norm.split() if t]
    return grams + tokens


class BM25Index:
    def __init__(self, documents: list[dict], k1: float = 1.5, b: float = 0.75):
        self.k1 = k1
        self.b = b
        self.docs_tokens: list[list[str]] = []
        lengths = []
        df: Counter[str] = Counter()
        for doc in documents:
            blob = " ".join(
                [doc.get("title", ""), doc.get("text", "")] + list(doc.get("questions") or [])
            )
            toks = [t for t in normalize(blob).split() if t]
            self.docs_tokens.append(toks)
            lengths.append(len(toks) or 1)
            df.update(set(toks))
        self.n = max(len(documents), 1)
        self.avgdl = sum(lengths) / max(len(lengths), 1)
        self.idf = {t: math.log((1 + self.n) / (1 + c)) + 1.0 for t, c in df.items()}

    def score(self, query: str, i: int) -> float:
        q_tokens = [t for t in normalize(query).split() if t]
        if not q_tokens:
            return 0.0
        tf = Counter(self.docs_tokens[i])
        dl = max(len(self.docs_tokens[i]), 1)
        total = 0.0
        for t in q_tokens:
            f = tf.get(t, 0)
            if f == 0:
                continue
            idf = self.idf.get(t, 0.0)
            denom = f + self.k1 * (1 - self.b + self.b * dl / self.avgdl)
            total += idf * (f * (self.k1 + 1)) / denom
        return total


class TfidfIndex:
    def __init__(self, documents: list[dict]):
        self.documents = documents
        self.df: Counter[str] = Counter()
        self.doc_tf: list[Counter[str]] = []
        for doc in documents:
            blob = " ".join(
                [doc.get("title", ""), doc.get("text", "")] + list(doc.get("questions") or [])
            )
            tf = Counter(features(blob))
            self.doc_tf.append(tf)
            self.df.update(tf.keys())
        self.n = max(len(documents), 1)
        self.idf = {term: math.log((1 + self.n) / (1 + df)) + 1.0 for term, df in self.df.items()}
        self.bm25 = BM25Index(documents)

    def _vec(self, tf: Counter[str]) -> dict[str, float]:
        return {t: tf[t] * self.idf.get(t, 0.0) for t in tf}

    @staticmethod
    def _cos(a: dict[str, float], b: dict[str, float]) -> float:
        if not a or not b:
            return 0.0
        dot = sum(a[k] * b[k] for k in a if k in b)
        na = math.sqrt(sum(v * v for v in a.values()))
        nb = math.sqrt(sum(v * v for v in b.values()))
        if na == 0 or nb == 0:
            return 0.0
        return dot / (na * nb)

    def search(self, query: str, lang: str, role: str, k: int = 3) -> list[tuple[float, dict]]:
        qv = self._vec(Counter(features(query)))
        scored = []
        for i, doc in enumerate(self.documents):
            roles = doc.get("roles") or []
            if roles and role not in roles:
                continue
            lang_ok = doc.get("lang") == lang
            dv = self._vec(self.doc_tf[i])
            score = self._cos(qv, dv)
            if lang_ok:
                score *= 1.15
            qn = normalize(query)
            for q in doc.get("questions") or []:
                if normalize(q) == qn:
                    score = max(score, 0.99)
                elif qn and (normalize(q) in qn or qn in normalize(q)):
                    score = max(score, 0.55)
            bm = self.bm25.score(query, i)
            scored.append((score, bm, doc))
        if not scored:
            return []
        max_bm = max(s[1] for s in scored) or 1.0
        mixed = []
        seen_ids = set()
        for tf, bm, doc in scored:
            hybrid = 0.6 * tf + 0.4 * (bm / max_bm)
            mixed.append((hybrid, doc))
        mixed.sort(key=lambda x: x[0], reverse=True)
        out = []
        for s, d in mixed:
            did = d.get("id")
            if did in seen_ids:
                continue
            seen_ids.add(did)
            if s > 0:
                out.append((s, d))
            if len(out) >= k:
                break
        return out


STOP = {
    "how", "do", "i", "the", "a", "an", "what", "where", "is", "are", "my", "me",
    "to", "for", "of", "and", "in", "on", "does", "please", "can", "you", "we",
    "كيف", "وين", "شلون", "بدي", "شو", "هل", "ما", "ماذا", "اين", "لي", "في",
    "من", "على", "يا", "كم", "هو", "هي", "هذا", "هذه", "عن", "او", "أو",
}
WEAK = {
    "طبيب", "الطبيب", "دكتور", "مريض", "المريض", "اداره", "الاداره", "ادارة",
    "doctor", "patient", "admin", "edoc", "demo", "منصه", "المنصه", "منصة",
    "حساب", "موعد", "جلسه", "جلسة", "منصة",
}


def doc_blob(doc: dict) -> str:
    return normalize(
        " ".join([doc.get("title", ""), doc.get("text", "")] + list(doc.get("questions") or []))
    )


def _fold(token: str) -> set[str]:
    forms = {token}
    if token.startswith("ال") and len(token) > 4:
        forms.add(token[2:])
    if len(token) > 4 and token[-1] in "يكةه":
        forms.add(token[:-1])
    return forms


def _edit1(a: str, b: str) -> bool:
    if a == b:
        return True
    if abs(len(a) - len(b)) > 1 or min(len(a), len(b)) < 4:
        return False
    if len(a) == len(b):
        return sum(x != y for x, y in zip(a, b)) == 1
    if len(a) > len(b):
        a, b = b, a
    i = 0
    for ch in b:
        if i < len(a) and a[i] == ch:
            i += 1
    return i == len(a)


def _token_hit(token: str, blob: str, blob_tokens: set[str]) -> bool:
    if token in blob:
        return True
    for form in _fold(token):
        if form in blob:
            return True
        for b in blob_tokens:
            if form in _fold(b) or _edit1(form, b):
                return True
    return False


def is_relevant(query: str, doc: dict, score: float) -> bool:
    if score >= 0.9:
        return True
    blob = doc_blob(doc)
    blob_tokens = {t for t in blob.split() if len(t) >= 3}
    q_tokens = [t for t in normalize(query).split() if len(t) >= 3 and t not in STOP]
    if not q_tokens:
        return score >= 0.22
    strong = [t for t in q_tokens if t not in WEAK]
    check = strong or q_tokens
    hits = sum(1 for t in check if _token_hit(t, blob, blob_tokens))
    if score >= 0.24 and hits >= 1:
        return True
    needed = max(1, (len(check) + 1) // 2)
    return hits >= needed
