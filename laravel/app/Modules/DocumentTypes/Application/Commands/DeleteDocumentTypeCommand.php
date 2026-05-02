<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\Commands;

final class DeleteDocumentTypeCommand
{
    public function __construct(
        public string $uuid,
    ) {}
}
