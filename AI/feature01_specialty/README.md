# Feature 01 — Specialty recommendation

Original LinearSVC artifacts in `artifacts/`. Do not retrain. Abstention rules stay in `artifacts/abstention_rules.json`.

```bash
docker compose -f AI/docker-compose.ai.yml up -d --build edoc-specialty
python tests/run_local_tests.py
```

Health: `http://127.0.0.1:8081/health`  
PHP: `AI_SERVICE_URL` + `AI_SERVICE_TOKEN`.
