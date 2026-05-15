<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Application\Handlers;

use App\Modules\AcademicDocuments\Application\DTOs\AcademicDocumentDTO;
use App\Modules\AcademicDocuments\Domain\Repositories\AcademicDocumentRepositoryInterface;

/**
 * Handler que lista todos los documentos académicos.
 * Lo usa el panel de administración.
 */
final class ListAcademicDocumentsHandler
{
    public function __construct(
        private readonly AcademicDocumentRepositoryInterface $repository,
    ) {}

    /**
     * @return AcademicDocumentDTO[]
     */
    public function handle(): array
    {
        $documents = $this->repository->findAll();

        // Convierte cada entidad a DTO antes de salir del dominio
        return array_map(
            fn($document) => AcademicDocumentDTO::fromEntity($document),
            $documents
        );
    }
}