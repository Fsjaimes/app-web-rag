<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\DTOs;
use App\Modules\DocumentTypes\Domain\Entities\DocumentType;
use App\Modules\DocumentTypes\Domain\ValueObjects\{DocumentTypeAffectsInventory, DocumentTypeHasPrefix, DocumentTypeHasDate, DocumentTypeStatus, DocumentTypeDateFormat, DocumentTypeInventoryMovementType, DocumentTypeAllowNegativeInventory, DocumentTypeLengthSequence};

final class SaveDocumentTypeDTO {
    public function __construct(
        public ?int $id,
        public string $uuid,
        public string $name,
        public string $prefix,
        public DocumentTypeAffectsInventory $affectsInventory,
        public ?DocumentTypeInventoryMovementType $inventoryMovementType,
        public DocumentTypeAllowNegativeInventory $allowNegativeInventory,
        public DocumentTypeHasPrefix $hasPrefix,
        public DocumentTypeHasDate $hasDate,
        public ?DocumentTypeDateFormat $dateFormat,
        public DocumentTypeLengthSequence $lengthSequence,
        public DocumentTypeStatus $status,
        public string $createdBy,
        public string $updatedBy,
    ) {}

    public static function fromDomain(DocumentType $documentType): self {
        return new self(
            id: $documentType->id(),
            uuid: $documentType->uuid(),
            name: $documentType->name(),
            prefix: $documentType->prefix(),
            affectsInventory: $documentType->affectsInventory(),
            inventoryMovementType: $documentType->inventoryMovementType(),
            allowNegativeInventory: $documentType->allowNegativeInventory(),
            hasPrefix: $documentType->hasPrefix(),
            hasDate: $documentType->hasDate(),
            dateFormat: $documentType->dateFormat(),
            lengthSequence: $documentType->lengthSequence(),
            status: $documentType->status(),
            createdBy: $documentType->createdBy(),
            updatedBy: $documentType->updatedBy(),
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'prefix' => $this->prefix,
            'affectsInventory' => $this->affectsInventory->toBool(),
            'inventoryMovementType' => $this->inventoryMovementType?->value(),
            'inventoryMovementTypeDescription' => $this->inventoryMovementType?->description(),
            'allowNegativeInventory' => $this->allowNegativeInventory->toBool(),
            'hasPrefix' => $this->hasPrefix->toBool(),
            'hasDate' => $this->hasDate->toBool(),
            'dateFormat' => $this->dateFormat?->value(),
            'dateFormatDescription' => $this->dateFormat?->description(),
            'lengthSequence' => $this->lengthSequence->value(),
            'lengthSequenceDescription' => $this->lengthSequence->description(),
            'status' => $this->status->toBool(),
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}