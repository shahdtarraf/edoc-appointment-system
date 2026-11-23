# Feature 02 — Smart assistant

Knowledge JSON in `knowledge_base/`. Hybrid TF-IDF + BM25. No paid LLM required.

```bash
docker compose -f AI/docker-compose.ai.yml up -d --build edoc-assistant
python tests/run_local_tests.py
python tests/run_qa_suite.py
python tests/run_enhance_suite.py
```

Health: `http://127.0.0.1:8082/health`  
PHP: `ASSISTANT_URL` + `ASSISTANT_TOKEN`.
