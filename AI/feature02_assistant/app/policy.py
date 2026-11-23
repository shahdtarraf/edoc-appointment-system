from __future__ import annotations

from .retrieve import detect_lang, normalize

MEDICAL_AR = (
    "تشخيص",
    "علاج",
    "دواء",
    "جرعه",
    "جرعة",
    "وصفه",
    "وصفة",
    "تحليل دم",
    "اشعه",
    "أشعة",
    "سرطان",
    "نزيف",
    "سكت",
    "جلطه",
)
MEDICAL_EN = (
    "diagnos",
    "prescri",
    "dosage",
    "medication",
    "medicine",
    "drug",
    "chemotherapy",
    "stroke",
    "cancer treatment",
)
PRIVACY_EXTRACT = (
    "اعطني",
    "أعطني",
    "show me",
    "list all",
    "reveal",
    "dump ",
    "ايميلات",
    "emails",
    "مريض آخر",
    "other patient",
    "private data",
    "رقم هاتف",
    "phone number",
    "what is my email",
    "ما هو ايميلي",
    "api key",
    "service token",
    "assistant_service",
    "assistant_llm",
    "ppassword",
    "docpassword",
    "@edoc.example",
)
PASSWORD_MARKERS = ("password", "كلمه مرور", "كلمة مرور", "كلمه المرور", "كلمة المرور")
PASSWORD_HOWTO = ("change", "reset", "update", "تعديل", "اغير", "تغير", "تغيير", "كيف")
INJECTION = (
    "ignore previous",
    "ignore all rules",
    "disregard context",
    "disregard all",
    "system prompt",
    "you are a doctor",
    "jailbreak",
    "تجاهل التعليمات",
    "تجاهل السياق",
)


def is_medical(text: str) -> bool:
    n = normalize(text)
    hay = text.lower()
    return any(w in n or w in hay for w in MEDICAL_AR + MEDICAL_EN)


def is_password_howto(text: str) -> bool:
    n = normalize(text)
    hay = text.lower()
    has_pw = any(normalize(w) in n or w in hay for w in PASSWORD_MARKERS)
    has_how = any(normalize(w) in n or w in hay for w in PASSWORD_HOWTO)
    return has_pw and has_how


def is_privacy(text: str) -> bool:
    if is_password_howto(text):
        return False
    n = normalize(text)
    hay = text.lower()
    if any(normalize(w) in n or w in hay for w in PRIVACY_EXTRACT):
        return True
    has_pw = any(normalize(w) in n or w in hay for w in PASSWORD_MARKERS)
    return has_pw and any(k in hay or normalize(k) in n for k in ("اعطني", "أعطني", "show", "reveal", "ما هي", "what is"))


def is_injection(text: str) -> bool:
    hay = (text or "").lower()
    n = normalize(text)
    return any(p in hay or normalize(p) in n for p in INJECTION)


def refuse_medical(lang: str, role: str) -> dict:
    if lang == "en":
        msg = (
            "I only help with using eDoc, not with medical advice, diagnosis, or treatment. "
            "If you want a specialty suggestion, open Specialty helper in the patient menu. "
            "For real care, speak with a clinician outside this demo."
        )
        src = "patient/specialty-assist.php"
    else:
        msg = (
            "أنا مساعد لاستخدام منصة eDoc فقط، ولست طبيباً ولا أقدم تشخيصاً أو علاجاً أو أدوية. "
            "لاختيار تخصص إرشادي استخدم صفحة مساعد التخصص من قائمة المريض. "
            "للرعاية الفعلية راجع مختصاً خارج هذه البيئة التجريبية."
        )
        src = "patient/specialty-assist.php"
    return {
        "status": "refuse_medical",
        "answer": msg,
        "source_label": "مساعد التخصص" if lang == "ar" else "Specialty helper",
        "source": src,
        "role_hint": role,
    }


def refuse_privacy(lang: str) -> dict:
    if lang == "en":
        msg = "I cannot look up passwords, tokens, or another person's records. Use Settings on your own account only."
    else:
        msg = "لا أستطيع عرض كلمات المرور أو بيانات حساب آخر. استخدم صفحة الإعدادات لحسابك فقط."
    return {
        "status": "refuse_privacy",
        "answer": msg,
        "source_label": "الإعدادات" if lang == "ar" else "Settings",
        "source": "settings.php",
    }


def abstain(lang: str) -> dict:
    if lang == "en":
        msg = "I do not have that in the eDoc help pages. Try the matching menu in your console, or rephrase a question about using the platform."
    else:
        msg = "هذه المعلومة غير متوفرة في صفحات مساعدة eDoc. جرّب القائمة الجانبية في لوحتك، أو أعد صياغة سؤال عن استخدام المنصة."
    return {
        "status": "abstain",
        "answer": msg,
        "source_label": None,
        "source": None,
    }


def refuse_injection(lang: str) -> dict:
    if lang == "en":
        msg = "I only answer questions about using eDoc. I cannot ignore those limits or invent platform facts."
    else:
        msg = "أجيب فقط عن استخدام منصة eDoc. لا يمكنني تجاهل هذه الحدود أو اختراع معلومات عن المنصة."
    return {
        "status": "refuse_injection",
        "answer": msg,
        "source_label": None,
        "source": None,
    }


def detect_query_lang(text: str) -> str:
    return detect_lang(text)
