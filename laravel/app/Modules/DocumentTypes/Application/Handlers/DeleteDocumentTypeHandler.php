<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\Handlers;

use App\Modules\DocumentTypes\Application\Commands\DeleteDocumentTypeCommand;
use App\Modules\DocumentTypes\Domain\Exceptions\DocumentTypeNotFoundException;
use App\Modules\DocumentTypes\Domain\Repositories\DocumentTypeRepositoryInterface;

final class DeleteDocumentTypeHandler
{
    public function __construct(private DocumentTypeRepositoryInterface $repo) {}

    public function handle(DeleteDocumentTypeCommand $command): void
    {
        $documentType = $this->repo->findByUuid($command->uuid);
        if ($documentType === null || $documentType->id() === null) {
            throw DocumentTypeNotFoundException::withId($command->uuid);
        }

        $this->repo->delete((string) $documentType->id());
    }
}
