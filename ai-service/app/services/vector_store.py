import chromadb
from app.config import settings

_collection = None


def get_collection() -> chromadb.Collection:
    """
    Singleton del cliente ChromaDB.
    La colección se crea si no existe, con distancia coseno
    (adecuada para embeddings de texto normalizados).
    """
    global _collection
    if _collection is None:
        client = chromadb.PersistentClient(path=settings.chroma_path)
        _collection = client.get_or_create_collection(
            name=settings.chroma_collection,
            metadata={"hnsw:space": "cosine"},
        )
    return _collection


def add_chunks(
    chroma_ids: list[str],
    embeddings: list[list[float]],
    documents: list[str],
    metadatas: list[dict],
) -> None:
    """Inserta chunks con sus embeddings en ChromaDB."""
    collection = get_collection()
    collection.add(
        ids=chroma_ids,
        embeddings=embeddings,
        documents=documents,
        metadatas=metadatas,
    )


def query_chunks(
    query_embedding: list[float],
    top_k: int,
) -> dict:
    """
    Busca los chunks más similares a la consulta.
    Devuelve el resultado raw de ChromaDB (ids, documents, metadatas, distances).
    """
    collection = get_collection()
    return collection.query(
        query_embeddings=[query_embedding],
        n_results=top_k,
        include=["documents", "metadatas", "distances"],
    )


def delete_chunks(chroma_ids: list[str]) -> None:
    """Elimina chunks por sus IDs de ChromaDB."""
    collection = get_collection()
    collection.delete(ids=chroma_ids)
