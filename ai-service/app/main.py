from fastapi import FastAPI

app = FastAPI(
    title="UTS AI Service",
    description="Microservicio RAG para el asistente virtual de la UTS",
    version="1.0.0"
)

@app.get("/health")
def health_check():
    return {"status": "ok", "service": "uts-fastapi"}