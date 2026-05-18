from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.routers import chat, documents

app = FastAPI(
    title="UTS AI Service",
    description="Microservicio RAG para el asistente virtual de las Unidades Tecnológicas de Santander",
    version="1.0.0",
)

# Laravel (Nginx en :8080) necesita poder llamar a este servicio
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(chat.router)
app.include_router(documents.router)


@app.get("/health")
def health_check():
    return {"status": "ok", "service": "uts-fastapi"}
