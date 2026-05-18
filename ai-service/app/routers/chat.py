import re

from fastapi import APIRouter, HTTPException
from app.schemas.chat import AskRequest, AskResponse, Source
from app.services import vector_store, llm
from app.config import settings

router = APIRouter(tags=["chat"])

# Palabras clave que indican preguntas sobre fechas/actividades del calendario
_DATE_QUERY_KEYWORDS = re.compile(
    r"\b(cuándo|cuando|fecha|fechas|inicio|iniciar|inician|empezar|empiezan|"
    r"vence|vencer|vencimiento|termina|terminar|finaliza|finalizar|plazo|"
    r"inscripci[oó]n|matr[ií]cula|liquidaci[oó]n|admisi[oó]n|publicaci[oó]n|"
    r"clases|semestre|vacaciones|periodo|período)\b",
    re.IGNORECASE,
)

# Patrón para identificar texto de resolución (no entradas de calendario)
_RESOLUTION_PATTERN = re.compile(
    r"(CONSEJO ACAD[EÉ]MICO|ACUERDO No|ARTÍCULO|"
    r"CONSIDERANDO|CONSTITUCIÓN POLÍTICA|"
    r"En uso de sus atribuciones|Por medio del cual)",
    re.IGNORECASE,
)


def _has_calendar_entry(text: str) -> bool:
    """Retorna True si el chunk contiene una entrada de calendario ACTIVIDAD → FECHA."""
    return bool(re.search(r"→\s*(DEL\b|HASTA\b|AL\b|\d{1,2}\s+DE\b)", text, re.IGNORECASE))


def _rerank(
    docs: list[str],
    metas: list[dict],
    distances: list[float],
    is_date_query: bool,
) -> list[tuple[str, dict, float]]:
    """
    Re-ordena los chunks recuperados para priorizar entradas de calendario
    sobre texto de resolución cuando la pregunta es sobre fechas/actividades.

    Estrategia:
      - Si es consulta de fechas: chunks con entradas de calendario van primero.
      - Excluir chunks con similitud muy baja (distancia > MAX_DISTANCE).
      - Eliminar duplicados de contenido (chunks casi idénticos al inicio).
    """
    MAX_DISTANCE = 0.85
    combined = list(zip(docs, metas, distances))

    # Filtrar por distancia
    combined = [(d, m, dist) for d, m, dist in combined if dist <= MAX_DISTANCE]

    if not is_date_query:
        return combined

    # Re-ranking: primero chunks con entradas de calendario, luego los demás.
    # El filtro de texto de resolución solo aplica a chunks del CALENDARIO (no a
    # otros documentos como el reglamento estudiantil, que son texto de artículos
    # por naturaleza y no deben ser penalizados).
    def _is_calendar_resolution(doc: str, meta: dict) -> bool:
        """True si el chunk es preámbulo legal del calendario (sin fechas útiles)."""
        title = meta.get("title", "").lower()
        is_calendar_doc = "calendario" in title
        return is_calendar_doc and _RESOLUTION_PATTERN.search(doc) and not _has_calendar_entry(doc)

    calendar_chunks = [(d, m, dist) for d, m, dist in combined if _has_calendar_entry(d)]
    resolution_chunks = [(d, m, dist) for d, m, dist in combined if _is_calendar_resolution(d, m)]
    other_chunks = [(d, m, dist) for d, m, dist in combined
                    if not _has_calendar_entry(d) and not _is_calendar_resolution(d, m)]

    # Limitar preámbulos del calendario a máximo 2 para no saturar el contexto
    return calendar_chunks + other_chunks + resolution_chunks[:2]

# Patrón para extraer el prefijo [Contexto: ...] inyectado por el extractor de PDF
_CONTEXT_PREFIX_RE = re.compile(r"^\[Contexto:\s*(.+?)\]\n?", re.MULTILINE)


def _format_chunk(doc: str, title: str) -> str:
    """
    Formatea un chunk para el prompt del LLM usando Markdown.

    Si el chunk comienza con un prefijo [Contexto: ...], lo extrae y lo
    presenta como subtítulo Markdown para que el modelo lo trate como
    encabezado estructural, no como texto a reproducir en su respuesta.
    """
    m = _CONTEXT_PREFIX_RE.match(doc)
    if m:
        section_label = m.group(1).strip()
        content = doc[m.end():].strip()
        return (
            f"#### {title}\n"
            f"**Aplica a:** {section_label}\n\n"
            f"{content}"
        )
    return f"#### {title}\n\n{doc}"


@router.post("/ask", response_model=AskResponse)
async def ask(request: AskRequest):
    """
    Pipeline RAG completo:
    1. Genera embedding de la pregunta
    2. Recupera los chunks más relevantes de ChromaDB
    3. Construye el contexto con los fragmentos (reformateando metadatos de sección)
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
    retrieved_distances: list[float] = results["distances"][0]

    is_date_query = bool(_DATE_QUERY_KEYWORDS.search(request.question))

    ranked = _rerank(retrieved_docs, retrieved_meta, retrieved_distances, is_date_query)

    if not ranked:
        context = ""
        sources = None
    else:
        context_parts = []
        sources: list[Source] = []

        for doc, meta, _dist in ranked:
            title = meta.get("title", "Documento sin título")
            chunk_index = meta.get("chunk_index", 0)

            context_parts.append(_format_chunk(doc, title))

            clean_doc = _CONTEXT_PREFIX_RE.sub("", doc).strip()
            sources.append(Source(
                title=title,
                chunk_index=chunk_index,
                excerpt=clean_doc[:300],
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
