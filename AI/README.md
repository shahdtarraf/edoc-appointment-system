# eDoc AI services (Demo)

All trained and conversational AI lives under this folder.

| Feature | Folder | Port | Role |
|---|---|---|---|
| 01 Specialty recommendation | `feature01_specialty/` | 8081 | Optional helper on patient Specialty helper page |
| 02 Smart assistant | `feature02_assistant/` | 8082 | Chat widget; platform help only |
| 03 No-show (shadow) | `feature03_noshow/` | 8083 | Admin-only research scores; **not commercial** |

PHP eDoc stays in `doctor-appointment-system/` and talks to these APIs only.

## Run all AI services

From repo root (with PHP Demo):

```bash
docker compose -f docker-compose.demo.yml up -d --build
```

AI only:

```bash
docker compose -f AI/docker-compose.ai.yml up -d --build
```

Single services: see each feature `README.md`.

## Health

- http://127.0.0.1:8081/health
- http://127.0.0.1:8082/health
- http://127.0.0.1:8083/health

Feature 03 starts without joblib files and reports `bundle.complete=false`. Drop the official package into `feature03_noshow/model_bundle/` for real inference. Threshold stays 0.20. License CC BY-NC-SA 4.0 — Demo research only, never production.
