<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\Services;

use App\Modules\DocumentTypes\Application\DTOs\DocumentTypeDTO;
use App\Modules\DocumentTypes\Domain\Repositories\DocumentTypeRepositoryInterface;

final class GetDocumentTypeByPrefixService
{
    public function __construct(
        private readonly DocumentTypeRepositoryInterface $documentTypeRepository,
    ) {}

    public function execute(string $prefix): ?DocumentTypeDTO
    {
        $documentType = $this->documentTypeRepository->findByPrefix($prefix);
        if ($documentType === null) {
            return null;
        }

        return DocumentTypeDTO::fromDomain($documentType);
    }
}
