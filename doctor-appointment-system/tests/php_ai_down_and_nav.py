"""AI-outage test on original DB, then optional nav-seed DB check.

Expects eDoc on :8080. Nav test expects DB_NAME=SQL_Database_edoc_ai_nav_test.
"""

from __future__ import annotations

import os
import re
import sys
import urllib.parse
import urllib.request
from http.cookiejar import CookieJar

BASE = "http://127.0.0.1:8080"


class Browser:
    def __init__(self) -> None:
        self.jar = CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))

    def get(self, path: str) -> str:
        with self.opener.open(urllib.request.Request(BASE + path), timeout=20) as resp:
            return resp.read().decode("utf-8", errors="replace")

    def post(self, path: str, data: dict[str, str]) -> str:
        body = urllib.parse.urlencode(data).encode("utf-8")
        req = urllib.request.Request(
            BASE + path,
            data=body,
            headers={"Content-Type": "application/x-www-form-urlencoded"},
        )
        with self.opener.open(req, timeout=20) as resp:
            return resp.read().decode("utf-8", errors="replace")


def csrf(html: str) -> str:
    match = re.search(r'name="csrf_token"\s+value="([^"]+)"', html)
    if not match:
        raise AssertionError("csrf_token missing")
    return match.group(1)


def login() -> Browser:
    browser = Browser()
    browser.get("/login.php")
    html = browser.post(
        "/login.php",
        {"useremail": "patient@edoc.com", "userpassword": "123"},
    )
    if "Welcome" not in html and "specialty-assist.php" not in html:
        raise AssertionError("login failed")
    return browser


def test_ai_down() -> None:
    browser = login()
    doctors = browser.get("/patient/doctors.php")
    assert "Test Doctor" in doctors
    sched = browser.get("/patient/schedule.php")
    assert "Test Session" in sched
    assist = browser.get("/patient/specialty-assist.php")
    html = browser.post(
        "/patient/specialty-assist.php",
        {
            "csrf_token": csrf(assist),
            "symptom_text": "لدي ألم شديد في الضرس",
        },
    )
    assert "خدمة المساعدة غير متاحة حالياً" in html, html[html.find("assist-note"): html.find("assist-note") + 400] if "assist-note" in html else html[:500]
    assert "اختيار يدوي" in html
    assert "doctors.php" in html
    print("PASS: AI down keeps manual doctors/sessions and helper fallback")


def test_nav_seed() -> None:
    browser = login()
    assist = browser.get("/patient/specialty-assist.php")
    html = browser.post(
        "/patient/specialty-assist.php",
        {
            "csrf_token": csrf(assist),
            "symptom_text": "لدي ألم شديد في الضرس مع تورم في اللثة وحساسية للماء البارد",
        },
    )
    if "Nav Test Dental" not in html and "عرض الأطباء المتاحين" not in html:
        raise AssertionError("nav seed did not expose doctor link")
    listing = browser.get("/patient/doctors.php?spec=11")
    assert "Nav Test Dental" in listing
    print("PASS: separate nav-test DB shows doctor link for specialty 11")


if __name__ == "__main__":
    mode = sys.argv[1] if len(sys.argv) > 1 else "ai-down"
    if mode == "ai-down":
        test_ai_down()
    elif mode == "nav":
        test_nav_seed()
    else:
        raise SystemExit("unknown mode")
