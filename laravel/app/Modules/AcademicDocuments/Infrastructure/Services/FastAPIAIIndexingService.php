<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Infrastructure\Services;

use Illuminate\Support\Facades\Http;

/**
 * Implementación concreta que indexa documentos en ChromaDB a través de FastAPI.
 *
 * El archivo se envía como multipart/form-data porque FastAPI y Laravel
 * corren en contenedores distintos y no comparten filesystem.
 * FastAPI procesa el PDF en memoria: lo divide en chunks,
 * genera embeddings y los almacena en ChromaDB.
 */
class FastAPIAIIndexingService implements AIIndexingServiceInterface
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ai_service.url'), '/');
        // La indexación puede tardar varios segundos — timeout extendido
        $this->timeout = config('services.ai_service.timeout', 30) * 4;
    }

    /**
     * Envía el PDF a FastAPI como multipart y devuelve los IDs de chunks en ChromaDB.
     *
     * @return string[] IDs de los chunks creados en ChromaDB
     */
    public function indexDocument(
        string $uuid,
        string $filePath,
        string $title,
    ): array {
        $response = Http::timeout($this->timeout)
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post("{$this->baseUrl}/index-document", [
                'uuid'  => $uuid,
                'title' => $title,
            ]);

        $response->throw();

        return $response->json('chroma_ids');
    }

    /**
     * Elimina los chunks de un documento de ChromaDB.
     *
     * @param string[] $chromaIds
     */
    public function deleteDocument(array $chromaIds): void
    {
        $response = Http::timeout($this->timeout)
            ->delete("{$this->baseUrl}/documents", [
                'chroma_ids' => $chromaIds,
            ]);

        $response->throw();
    }
}
