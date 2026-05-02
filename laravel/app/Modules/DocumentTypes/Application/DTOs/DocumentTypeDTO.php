<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\DTOs;

use App\Modules\DocumentTypes\Domain\Entities\DocumentType;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeAffectsInventory;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeAllowNegativeInventory;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeDateFormat;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeHasDate;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeHasPrefix;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeInventoryMovementType;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeLengthSequence;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeStatus;

final class DocumentTypeDTO
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public string $prefix,
        public ?DocumentTypeInventoryMovementType $inventoryMovementType,
        public DocumentTypeAffectsInventory $affectsInventory,
        public DocumentTypeAllowNegativeInventory $allowNegativeInventory,
        public DocumentTypeHasPrefix $hasPrefix,
        public DocumentTypeHasDate $hasDate,
        public ?DocumentTypeDateFormat $dateFormat,
        public DocumentTypeLengthSequence $lengthSequence,
        public DocumentTypeStatus $status,
    ) {}

    public static function fromDomain(DocumentType $documentType): self
    {
        return new self(
            id: (int) $documentType->id(),
            uuid: $documentType->uuid(),
            name: $documentType->name(),
            prefix: $documentType->prefix(),
            inventoryMovementType: $documentType->inventoryMovementType(),
            affectsInventory: $documentType->affectsInventory(),
            allowNegativeInventory: $documentType->allowNegativeInventory(),
            hasPrefix: $documentType->hasPrefix(),
            hasDate: $documentType->hasDate(),
            dateFormat: $documentType->dateFormat(),
            lengthSequence: $documentType->lengthSequence(),
            status: $documentType->status(),
        );
    }
}
