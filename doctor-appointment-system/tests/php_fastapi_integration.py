"""Live HTTP tests against local eDoc PHP (port 8080) and FastAPI (port 8081).

Uses the original dump database. Does not insert doctors into SQL_Database_edoc.
"""

from __future__ import annotations

import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from http.cookiejar import CookieJar

BASE = "http://127.0.0.1:8080"
AI = "http://127.0.0.1:8081"
PATIENT_EMAIL = "patient@edoc.com"
PATIENT_PASSWORD = "123"


class Browser:
    def __init__(self) -> None:
        self.jar = CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))

    def get(self, path: str) -> tuple[int, str]:
        req = urllib.request.Request(BASE + path, method="GET")
        try:
            with self.opener.open(req, timeout=20) as resp:
                return resp.status, resp.read().decode("utf-8", errors="replace")
        except urllib.error.HTTPError as exc:
            return exc.code, exc.read().decode("utf-8", errors="replace")

    def post(self, path: str, data: dict[str, str]) -> tuple[int, str, str]:
        body = urllib.parse.urlencode(data).encode("utf-8")
        req = urllib.request.Request(
            BASE + path,
            data=body,
            headers={"Content-Type": "application/x-www-form-urlencoded"},
            method="POST",
        )
        try:
            with self.opener.open(req, timeout=20) as resp:
                return resp.status, resp.read().decode("utf-8", errors="replace"), resp.geturl()
        except urllib.error.HTTPError as exc:
            return exc.code, exc.read().decode("utf-8", errors="replace"), BASE + path


def csrf_from(html: str) -> str:
    match = re.search(r'name="csrf_token"\s+value="([^"]+)"', html)
    if not match:
        raise AssertionError("csrf_token not found")
    return match.group(1)


def main() -> int:
    failures: list[str] = []

    with urllib.request.urlopen(AI + "/health", timeout=10) as resp:
        health = json.loads(resp.read().decode("utf-8"))
    if not health.get("ok"):
        failures.append(f"FastAPI health failed: {health}")

    with urllib.request.urlopen(BASE + "/health.php", timeout=15) as resp:
        php_health = json.loads(resp.read().decode("utf-8"))
    if php_health.get("ok") is not True or php_health.get("database") is not True:
        failures.append(f"PHP health failed: {php_health}")
        print("FAILED")
        for item in failures:
            print(" -", item)
        return 1

    browser = Browser()
    status, login_html = browser.get("/login.php")
    if status != 200:
        failures.append(f"login GET {status}")

    status, body, url = browser.post(
        "/login.php",
        {"useremail": PATIENT_EMAIL, "userpassword": PATIENT_PASSWORD},
    )
    if "patient/index.php" not in url and "Welcome" not in body:
        failures.append(f"login did not reach patient home: status={status} url={url}")

    status, home = browser.get("/patient/index.php")
    if status != 200 or "specialty-assist.php" not in home:
        failures.append("home missing specialty helper link")

    status, doctors = browser.get("/patient/doctors.php")
    if status != 200 or "Test Doctor" not in doctors:
        failures.append("doctors.php did not list original Test Doctor")

    status, spec_page = browser.get("/patient/doctors.php?spec=11")
    if status != 200:
        failures.append(f"doctors.php?spec=11 status {status}")
    if "No doctors are currently listed for this specialty" not in spec_page:
        failures.append("missing no-doctors message on spec=11")
    if "Filtered specialty" not in spec_page:
        failures.append("specialty filter banner missing")

    status, assist = browser.get("/patient/specialty-assist.php")
    if status != 200:
        failures.append(f"specialty-assist GET {status}")
    if "ساعدني في اختيار التخصص المناسب" not in assist:
        failures.append("assist title missing")
    token = csrf_from(assist)

    status, rec_html, _ = browser.post(
        "/patient/specialty-assist.php",
        {
            "csrf_token": token,
            "symptom_text": "لدي ألم شديد في الضرس مع تورم في اللثة وحساسية للماء البارد",
        },
    )
    if status != 200:
        failures.append(f"recommend POST {status}")
    if "Dental, oral and maxillo-facial surgery" not in rec_html:
        failures.append("dental recommendation not shown")
    if "لا يوجد أطباء مسجّلون حالياً لهذا التخصص" not in rec_html:
        failures.append("missing no-doctor message after recommendation")
    if "95%" in rec_html or "احتمالية" in rec_html:
        failures.append("probability language leaked")

    token = csrf_from(rec_html)
    status, abstain_html, _ = browser.post(
        "/patient/specialty-assist.php",
        {"csrf_token": token, "symptom_text": "تعب عام"},
    )
    if "لم نتمكن من تحديد التخصص المناسب" not in abstain_html:
        failures.append("abstain message missing for تعب عام")
    if "اختيار يدوي" not in abstain_html:
        failures.append("manual choice missing after abstain")

    token = csrf_from(abstain_html)
    status, empty_html, _ = browser.post(
        "/patient/specialty-assist.php",
        {"csrf_token": token, "symptom_text": ""},
    )
    if "لم نتمكن من تحديد التخصص المناسب" not in empty_html and "خدمة المساعدة غير متاحة" not in empty_html:
        failures.append("empty text did not abstain or fail safely")

    status, sched = browser.get("/patient/schedule.php")
    if status != 200 or "Test Session" not in sched:
        failures.append("schedule.php missing Test Session")

    status, appts = browser.get("/patient/appointment.php")
    if status != 200:
        failures.append(f"appointment.php {status}")

    status, book = browser.get("/patient/booking.php?id=1")
    if status != 200:
        failures.append(f"booking.php {status}")
    if "booknow" not in book.lower() and "Book now" not in book:
        failures.append("booking form missing")
    book_token = csrf_from(book)
    status, booked, book_url = browser.post(
        "/patient/booking-complete.php",
        {"csrf_token": book_token, "scheduleid": "1", "booknow": "Book now"},
    )
    if status != 200:
        failures.append(f"booking-complete {status}")
    if "Database connection failed" in booked:
        failures.append("booking-complete database failed")

    if failures:
        print("FAILED")
        for item in failures:
            print(" -", item)
        return 1
    print("PASS: PHP+FastAPI live integration on original dump (no extra doctors)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
