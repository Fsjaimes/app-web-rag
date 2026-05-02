<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\Handlers;

use App\Modules\DocumentTypes\Domain\Repositories\DocumentTypeRepositoryInterface;

final class ValidateDocumentTypePrefixHandler
{
    public function __construct(private DocumentTypeRepositoryInterface $repo) {}

    public function handle(string $prefix, ?string $excludeUuid = null): bool
    {
        return $this->repo->prefixExists($prefix, $excludeUuid);
    }
}
