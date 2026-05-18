from fastapi import APIRouter, HTTPException
from app.schemas.chat import AskRequest, AskResponse, Source
from app.services import vector_store, llm
from app.config import settings

router = APIRouter(tags=["chat"])


@router.post("/ask", response_model=AskResponse)
async def ask(request: AskRequest):
    """
    Pipeline RAG completo:
    1. Genera embedding de la pregunta
    2. Recupera los chunks más relevantes de ChromaDB
    3. Construye el contexto con los fragmentos
    4. Llama al LLM con el contexto + historial + pregunta
    5. Devuelve la respuesta y las fuentes usadas
    """
    # 1. Embedding de la pregunta
    try:
        query_embedding = llm.get_embedding(request.question)
    except Exception as e:
        raise HTTPException(status_code=502, detail=f"Error generando embedding: {e}")

    # 2. Búsqueda semántica en ChromaDB
    try:
        results = vector_store.query_chunks(
            query_embedding=query_embedding,
            top_k=settings.rag_top_k,
        )
    except Exception as e:
        raise HTTPException(status_code=502, detail=f"Error consultando ChromaDB: {e}")

    # 3. Construir contexto y fuentes a partir de los chunks recuperados
    retrieved_docs: list[str] = results["documents"][0]
    retrieved_meta: list[dict] = results["metadatas"][0]

    if not retrieved_docs:
        # No hay documentos indexados aún — respuesta sin contexto RAG
        context = ""
        sources = None
    else:
        context_parts = []
        sources: list[Source] = []

        for i, (doc, meta) in enumerate(zip(retrieved_docs, retrieved_meta)):
            title = meta.get("title", "Documento sin título")
            chunk_index = meta.get("chunk_index", i)

            context_parts.append(f"[{title}]\n{doc}")
            sources.append(Source(
                title=title,
                chunk_index=chunk_index,
                excerpt=doc[:300],
            ))

        context = "\n\n---\n\n".join(context_parts)

    # 4. Historial serializado para el LLM
    history = [{"role": m.role, "content": m.content} for m in request.history]

    # 5. Llamada al LLM
    try:
        answer = llm.chat_completion(
            question=request.question,
            context=context,
            history=history,
        )
    except Exception as e:
        raise HTTPException(status_code=502, detail=f"Error llamando al LLM: {e}")

    return AskResponse(answer=answer, sources=sources)
