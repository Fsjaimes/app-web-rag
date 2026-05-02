<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Domain\Exceptions;

use Exception;

final class DocumentTypeNotFoundException extends Exception
{
    public static function withId(string $id): self
    {
        return new self("DocumentType with ID {$id} not found");
    }
}