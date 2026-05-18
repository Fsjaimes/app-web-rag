from fastapi import APIRouter, UploadFile, File, Form, HTTPException
from app.schemas.documents import IndexDocumentResponse, DeleteDocumentRequest
from app.services import pdf_processor, vector_store, llm
from app.config import settings
import uuid as uuid_lib

router = APIRouter(tags=["documents"])


@router.post("/index-document", response_model=IndexDocumentResponse)
async def index_document(
    file: UploadFile = File(...),
    uuid: str = Form(...),
    title: str = Form(...),
):
    """
    Recibe un PDF de Laravel, lo divide en chunks, genera embeddings
    y los almacena en ChromaDB.

    Devuelve los IDs de los chunks creados para que Laravel los guarde
    en academic_documents.chroma_ids y pueda eliminarlos después.
    """
    if file.content_type not in ("application/pdf", "application/octet-stream"):
        raise HTTPException(status_code=422, detail="El archivo debe ser un PDF.")

    # 1. Leer bytes del archivo
    file_bytes = await file.read()

    # 2. Extraer texto del PDF
    try:
        text = pdf_processor.extract_text(file_bytes)
    except Exception as e:
        raise HTTPException(status_code=422, detail=f"No se pudo leer el PDF: {e}")

    if not text.strip():
        raise HTTPException(status_code=422, detail="El PDF no contiene texto extraíble.")

    # 3. Dividir en chunks
    chunks = pdf_processor.chunk_text(
        text,
        chunk_size=settings.rag_chunk_size,
        overlap=settings.rag_chunk_overlap,
    )

    # 4. Generar embeddings en batch (una sola llamada a OpenAI)
    embeddings = llm.get_embeddings(chunks)

    # 5. Construir IDs y metadatos para cada chunk
    chroma_ids = [f"{uuid}_chunk_{i}" for i in range(len(chunks))]
    metadatas = [
        {"document_uuid": uuid, "title": title, "chunk_index": i}
        for i in range(len(chunks))
    ]

    # 6. Almacenar en ChromaDB
    vector_store.add_chunks(
        chroma_ids=chroma_ids,
        embeddings=embeddings,
        documents=chunks,
        metadatas=metadatas,
    )

    return IndexDocumentResponse(
        chroma_ids=chroma_ids,
        chunks_count=len(chunks),
    )


@router.delete("/documents")
async def delete_document(body: DeleteDocumentRequest):
    """
    Elimina los chunks de un documento de ChromaDB.
    Laravel llama a este endpoint antes de borrar el registro de la BD.
    """
    if not body.chroma_ids:
        raise HTTPException(status_code=422, detail="chroma_ids no puede estar vacío.")

    try:
        vector_store.delete_chunks(body.chroma_ids)
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error al eliminar de ChromaDB: {e}")

    return {"deleted": len(body.chroma_ids)}
