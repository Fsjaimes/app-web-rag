<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\Handlers;

use App\Modules\DocumentTypes\Application\DTOs\DocumentTypeCollectionDTO;
use App\Modules\DocumentTypes\Domain\Repositories\DocumentTypeRepositoryInterface;

final class ListDocumentTypesHandler
{
    public function __construct(
        private DocumentTypeRepositoryInterface $repo,
    ) {}

    public function handle(array $filters = []): DocumentTypeCollectionDTO
    {
        $result = $this->repo->list($filters);
        return DocumentTypeCollectionDTO::fromDomain(
            $result['data'] ?? [],
            $result['total'] ?? 0,
        );
    }
}
