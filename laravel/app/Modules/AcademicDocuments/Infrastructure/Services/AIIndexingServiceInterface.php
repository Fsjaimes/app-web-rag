<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Infrastructure\Services;

/**
 * Contrato para el servicio que indexa documentos en ChromaDB via FastAPI.
 *
 * Separarlo como interfaz permite testearlo con un mock
 * sin necesitar FastAPI corriendo en los tests.
 */
interface AIIndexingServiceInterface
{
    /**
     * Envía un documento a FastAPI para indexarlo en ChromaDB.
     *
     * @return string[] IDs de los chunks creados en ChromaDB
     */
    public function indexDocument(
        string $uuid,
        string $filePath,
        string $title,
    ): array;

    /**
     * Elimina los chunks de un documento de ChromaDB.
     *
     * @param string[] $chromaIds
     */
    public function deleteDocument(array $chromaIds): void;
}