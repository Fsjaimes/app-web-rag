from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    # NVIDIA NIM — requerido
    nvidia_api_key: str
    nvidia_api_url: str = "https://integrate.api.nvidia.com/v1"
    nvidia_chat_model: str = "google/gemma-3n-e2b-it"

    # Embeddings — generados localmente con sentence-transformers
    # (no consume créditos de la API)
    embedding_model: str = "paraphrase-multilingual-MiniLM-L12-v2"

    # ChromaDB
    chroma_path: str = "/app/chroma_db"
    chroma_collection: str = "uts_documents"

    # RAG — parámetros de recuperación
    rag_top_k: int = 4          # chunks a recuperar por consulta
    rag_chunk_size: int = 1000  # caracteres por chunk
    rag_chunk_overlap: int = 200

    class Config:
        env_file = ".env"
        env_file_encoding = "utf-8"


settings = Settings()
