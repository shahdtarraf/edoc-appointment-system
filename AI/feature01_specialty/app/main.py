from __future__ import annotations

import logging
from contextlib import asynccontextmanager

from fastapi import FastAPI
from pydantic import BaseModel, Field

from . import model
from .security import RecommendGuardMiddleware

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("specialty-recommend")


class RecommendRequest(BaseModel):
    text: str = Field(default="", max_length=1500)


@asynccontextmanager
async def lifespan(_app: FastAPI):
    logger.info("Loading specialty recommendation artifacts")
    model.load_artifacts()
    logger.info("Model loaded; n_specialties=%s", len(model.ID_TO_NAME))
    yield


app = FastAPI(
    title="eDoc Specialty Recommendation",
    version="0.1.0",
    lifespan=lifespan,
    docs_url=None,
    redoc_url=None,
    openapi_url=None,
)
app.add_middleware(RecommendGuardMiddleware)


@app.get("/health")
def health():
    payload = model.health_payload()
    if not payload["ok"]:
        return payload
    return payload


@app.post("/recommend")
def recommend(req: RecommendRequest):
    # Do not log the medical text.
    result = model.recommend(req.text)
    logger.info(
        "recommend status=%s chars=%s",
        result.get("status"),
        len(req.text or ""),
    )
    return result
