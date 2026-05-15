<?php
declare(strict_types=1);

namespace App\Modules\Assistants\Domain\Exceptions;

use Exception;

final class AssistantNotFoundException extends Exception
{
    public static function withId(string $id): self
    {
        return new self("Assistant with ID {$id} not found");
    }
}