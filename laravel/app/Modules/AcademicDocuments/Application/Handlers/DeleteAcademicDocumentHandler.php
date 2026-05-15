<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Application\Handlers;

use App\Modules\AcademicDocuments\Application\Commands\DeleteAcademicDocumentCommand;
use App\Modules\AcademicDocuments\Domain\Repositories\AcademicDocumentRepositoryInterface;
use App\Modules\AcademicDocuments\Infrastructure\Services\AIIndexingServiceInterface;

/**
 * Handler que elimina un documento de PostgreSQL y de ChromaDB.
 *
 * Es importante eliminar de ChromaDB primero — si solo se elimina
 * de PostgreSQL, los vectores quedan huérfanos y el chatbot
 * seguiría respondiendo con ese contenido.
 */
final class DeleteAcademicDocumentHandler
{
    public function __construct(
        private readonly AcademicDocumentRepositoryInterface $repository,
        private readonly AIIndexingServiceInterface          $indexingService,
    ) {}

    public function handle(DeleteAcademicDocumentCommand $command): void
    {
        // 1. Buscar el documento para obtener sus chromaIds
        $document = $this->repository->findByUuid($command->uuid);

        if ($document === null) {
            throw new \RuntimeException(
                "Documento no encontrado: {$command->uuid}"
            );
        }

        // 2. Eliminar de ChromaDB si fue indexado
        if ($document->isIndexed() && $document->chromaIds() !== null) {
            $this->indexingService->deleteDocument($document->chromaIds());
        }

        // 3. Eliminar de PostgreSQL
        $this->repository->delete($command->uuid);
    }
}