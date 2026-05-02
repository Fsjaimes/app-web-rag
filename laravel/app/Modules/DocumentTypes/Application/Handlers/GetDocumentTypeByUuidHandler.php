<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\Handlers;

use App\Modules\DocumentTypes\Application\DTOs\SaveDocumentTypeDTO;
use App\Modules\DocumentTypes\Domain\Exceptions\DocumentTypeNotFoundException;
use App\Modules\DocumentTypes\Domain\Repositories\DocumentTypeRepositoryInterface;

final class GetDocumentTypeByUuidHandler
{
    public function __construct(private DocumentTypeRepositoryInterface $repo) {}

    public function handle(string $uuid): SaveDocumentTypeDTO
    {
        $documentType = $this->repo->findByUuid($uuid);
        if ($documentType === null) {
            throw DocumentTypeNotFoundException::withId($uuid);
        }

        return SaveDocumentTypeDTO::fromDomain($documentType);
    }
}
