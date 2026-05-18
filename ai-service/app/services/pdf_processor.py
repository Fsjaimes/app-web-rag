from pypdf import PdfReader


def extract_text(file_bytes: bytes) -> str:
    """Extrae todo el texto de un PDF recibido como bytes."""
    import io
    reader = PdfReader(io.BytesIO(file_bytes))
    pages = [page.extract_text() or "" for page in reader.pages]
    return "\n\n".join(pages)


def chunk_text(text: str, chunk_size: int = 1000, overlap: int = 200) -> list[str]:
    """
    Divide el texto en chunks de tamaño fijo con solapamiento.
    El solapamiento evita que una idea quede partida entre dos chunks,
    lo que mejoraría la calidad de la recuperación RAG.
    """
    chunks: list[str] = []
    start = 0
    text_length = len(text)

    while start < text_length:
        end = min(start + chunk_size, text_length)
        chunk = text[start:end].strip()
        if chunk:
            chunks.append(chunk)
        start += chunk_size - overlap

    return chunks
