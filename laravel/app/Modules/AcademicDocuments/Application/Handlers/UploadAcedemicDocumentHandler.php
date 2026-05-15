<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Application\Handlers;

use App\Modules\AcademicDocuments\Application\Commands\UploadAcademicDocumentCommand;
use App\Modules\AcademicDocuments\Application\DTOs\AcademicDocumentDTO;
use App\Modules\AcademicDocuments\Domain\Entities\AcademicDocument;
use App\Modules\AcademicDocuments\Domain\Repositories\AcademicDocumentRepositoryInterface;
use Illuminate\Support\Str;

/**
 * Handler que procesa la subida de un documento académico.
 *
 * Flujo:
 * 1. Crea la entidad AcademicDocument en estado 'pending'
 * 2. La persiste en PostgreSQL
 * 3. Delega la indexación a AIIndexingService (que llama a FastAPI)
 * 4. Actualiza el estado según la respuesta de FastAPI
 * 5. Devuelve el DTO con el resultado
 *
 * AIIndexingService se inyecta como interfaz para mantener
 * el Handler independiente de la implementación concreta.
 */
final class UploadAcademicDocumentHandler
{
    public function __construct(
        private readonly AcademicDocumentRepositoryInterface $repository,
        // La interfaz del servicio de indexación se define en Infrastructure
        // pero se inyecta aquí para mantener el Handler testeable
        private readonly \App\Modules\AcademicDocuments\Infrastructure\Services\AIIndexingServiceInterface $indexingService,
    ) {}

    public function handle(UploadAcademicDocumentCommand $command): AcademicDocumentDTO
    {
        $dto = $command->dto;

        // 1. Crear la entidad en estado pending
        $document = AcademicDocument::create(
            uuid:       (string) Str::uuid(),
            title:      $dto->title,
            filename:   $dto->filename,
            mimeType:   $dto->mimeType,
            sizeBytes:  $dto->sizeBytes,
            uploadedBy: $dto->uploadedBy,
        );

        // 2. Persistir en PostgreSQL antes de llamar a FastAPI
        //    Si FastAPI falla, el documento queda en 'pending'
        //    y puede reintentarse después
        $this->repository->save($document);

        try {
            // 3. Marcar como en proceso y actualizar en BD
            $document->markAsProcessing();
            $this->repository->save($document);

            // 4. Llamar a FastAPI para indexar el documento en ChromaDB
            $chromaIds = $this->indexingService->indexDocument(
                uuid:     $document->uuid(),
                filePath: $dto->filePath,
                title:    $dto->title,
            );

            // 5. Marcar como indexado con los IDs de ChromaDB
            $document->markAsIndexed($chromaIds);

        } catch (\Exception $e) {
            // Si FastAPI falla, guardamos el error pero no lanzamos excepción
            // El admin puede ver el error en el panel y reintentar
            $document->markAsError($e->getMessage());
        }

        // 6. Guardar estado final
        $this->repository->save($document);

        return AcademicDocumentDTO::fromEntity($document);
    }
}