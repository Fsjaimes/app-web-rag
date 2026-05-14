<?php
declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Domain\Exceptions;

use Exception;

final class AcademicDocumentNotFoundException extends Exception
{
    public static function withId(string $id): self
    {
        return new self("AcademicDocument with ID {$id} not found");
    }
}