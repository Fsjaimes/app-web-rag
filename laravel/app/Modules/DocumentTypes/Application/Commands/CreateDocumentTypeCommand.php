<?php

declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\Commands;

class CreateDocumentTypeCommand
{
    public function __construct(
        public string $prefix,
        public string $name,
        public bool $affectsInventory,
        public ?int $inventoryMovementType,
        public bool $allowNegativeInventory,
        public bool $hasPrefix,
        public bool $hasDate,
        public ?string $dateFormat,
        public int $lengthSequence,
        public bool $status,
        public string $actorUuid,
    ) {}
}