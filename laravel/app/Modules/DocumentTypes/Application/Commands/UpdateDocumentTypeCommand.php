<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\Commands;

final class UpdateDocumentTypeCommand
{
    public function __construct(
        public string $uuid,
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
