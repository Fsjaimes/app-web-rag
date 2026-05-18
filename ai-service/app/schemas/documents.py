from pydantic import BaseModel


class IndexDocumentResponse(BaseModel):
    chroma_ids: list[str]
    chunks_count: int


class DeleteDocumentRequest(BaseModel):
    chroma_ids: list[str]
