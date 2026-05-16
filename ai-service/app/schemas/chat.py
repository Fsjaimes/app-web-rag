from pydantic import BaseModel, Field
from typing import Optional


class HistoryMessage(BaseModel):
    role: str    # "user" | "assistant"
    content: str


class AskRequest(BaseModel):
    question: str = Field(..., min_length=2, max_length=2000)
    conversation_id: str
    history: list[HistoryMessage] = []


class Source(BaseModel):
    title: str
    chunk_index: int
    excerpt: str  # primeros 300 caracteres del chunk para mostrar en UI


class AskResponse(BaseModel):
    answer: str
    sources: Optional[list[Source]] = None
