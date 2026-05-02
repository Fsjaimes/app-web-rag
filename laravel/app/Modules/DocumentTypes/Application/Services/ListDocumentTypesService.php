<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\Services;

use App\Modules\DocumentTypes\Domain\Repositories\DocumentTypeRepositoryInterface;
use App\Modules\DocumentTypes\Application\DTOs\DocumentTypeCollectionDTO;

final class ListDocumentTypesService
{
    public function __construct(
        private readonly DocumentTypeRepositoryInterface $documentTypeRepository,
    ) {}

    public function execute(array $filters = []): DocumentTypeCollectionDTO
    {
        $documentTypes = $this->documentTypeRepository->list($filters);
        return DocumentTypeCollectionDTO::fromDomain(
            $documentTypes['data'] ?? [],
            $documentTypes['total'] ?? 0,
        );
    }
}