# eDoc specialty recommendation service

Experimental FastAPI inference for Feature 01. Covers **30** eDoc specialties only. Not a clinical product.

## Endpoints

- `GET /health`
- `POST /recommend` JSON `{"text": "..."}`

Decision scores are uncalibrated LinearSVC margins. They are never returned as percentages.

## Local

```
python -m venv .venv
.venv\Scripts\python -m pip install -r requirements.txt
.venv\Scripts\python tests\run_local_tests.py
.venv\Scripts\python -m uvicorn app.main:app --host 127.0.0.1 --port 8081
```

## Deploy

CPU only. Set Render health check to `/health`. PHP uses `AI_SERVICE_URL`.
