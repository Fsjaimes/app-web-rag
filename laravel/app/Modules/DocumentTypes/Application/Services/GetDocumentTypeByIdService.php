<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\Services;

use App\Modules\DocumentTypes\Application\DTOs\DocumentTypeDTO;
use App\Modules\DocumentTypes\Domain\Repositories\DocumentTypeRepositoryInterface;

final class GetDocumentTypeByIdService
{
    public function __construct(
        private readonly DocumentTypeRepositoryInterface $documentTypeRepository,
    ) {}

    public function execute(?int $id): ?DocumentTypeDTO
    {
        if ($id === null) {
            return null;
        }

        $documentType = $this->documentTypeRepository->findById((string) $id);
        if ($documentType === null) {
            return null;
        }

        return DocumentTypeDTO::fromDomain($documentType);
    }
}
