<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Domain\Entities;

use App\Modules\DocumentTypes\Domain\ValueObjects\{DocumentTypeAffectsInventory, DocumentTypeHasPrefix, DocumentTypeHasDate, DocumentTypeStatus, DocumentTypeDateFormat, DocumentTypeInventoryMovementType, DocumentTypeAllowNegativeInventory, DocumentTypeLengthSequence};
use DateTimeImmutable;

final class DocumentType
{
    public function __construct(
        private ?int $id,
        private string $uuid,
        private string $name,
        private string $prefix,
        private DocumentTypeAffectsInventory $affectsInventory,
        private ?DocumentTypeInventoryMovementType $inventoryMovementType,
        private DocumentTypeAllowNegativeInventory $allowNegativeInventory,
        private DocumentTypeHasPrefix $hasPrefix,
        private DocumentTypeHasDate $hasDate,
        private ?DocumentTypeDateFormat $dateFormat,
        private ?DocumentTypeLengthSequence $lengthSequence,
        private DocumentTypeStatus $status,
        private string $createdBy,
        private string $updatedBy,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt,
    ) {}

    public static function create(?int $id, string $uuid, string $name, string $prefix, DocumentTypeAffectsInventory $affectsInventory, ?DocumentTypeInventoryMovementType $inventoryMovementType, DocumentTypeAllowNegativeInventory $allowNegativeInventory, DocumentTypeHasPrefix $hasPrefix, DocumentTypeHasDate $hasDate, ?DocumentTypeDateFormat $dateFormat, ?DocumentTypeLengthSequence $lengthSequence, DocumentTypeStatus $status, string $createdBy): self
    {
        return new self(
            id: $id,
            uuid: $uuid,
            name: $name,
            prefix: $prefix,
            affectsInventory: $affectsInventory,
            inventoryMovementType: $inventoryMovementType,
            allowNegativeInventory: $allowNegativeInventory,
            hasPrefix: $hasPrefix,
            hasDate: $hasDate,
            dateFormat: $dateFormat,
            lengthSequence: $lengthSequence,
            status: $status,
            createdBy: $createdBy,
            updatedBy: $createdBy,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            deletedAt: null,
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }
    
    public function uuid(): string
    {
        return $this->uuid;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function affectsInventory(): DocumentTypeAffectsInventory
    {
        return $this->affectsInventory;
    }

    public function inventoryMovementType(): ?DocumentTypeInventoryMovementType
    {
        return $this->inventoryMovementType;
    }

    public function allowNegativeInventory(): DocumentTypeAllowNegativeInventory
    {
        return $this->allowNegativeInventory;
    }

    public function hasPrefix(): DocumentTypeHasPrefix
    {
        return $this->hasPrefix;
    }

    public function hasDate(): DocumentTypeHasDate
    {
        return $this->hasDate;
    }

    public function dateFormat(): ?DocumentTypeDateFormat
    {
        return $this->dateFormat;
    }

    public function lengthSequence(): ?DocumentTypeLengthSequence
    {
        return $this->lengthSequence;
    }

    public function status(): DocumentTypeStatus
    {
        return $this->status;
    }

    public function createdBy(): string
    {
        return $this->createdBy;
    }

    public function updatedBy(): string
    {
        return $this->updatedBy;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function update(
        string $name,
        string $prefix,
        DocumentTypeAffectsInventory $affectsInventory,
        ?DocumentTypeInventoryMovementType $inventoryMovementType,
        DocumentTypeAllowNegativeInventory $allowNegativeInventory,
        DocumentTypeHasPrefix $hasPrefix,
        DocumentTypeHasDate $hasDate,
        ?DocumentTypeDateFormat $dateFormat,
        ?DocumentTypeLengthSequence $lengthSequence,
        DocumentTypeStatus $status,
        string $updatedBy,
    ): void {
        $this->name = $name;
        $this->prefix = $prefix;
        $this->affectsInventory = $affectsInventory;
        $this->inventoryMovementType = $inventoryMovementType;
        $this->allowNegativeInventory = $allowNegativeInventory;
        $this->hasPrefix = $hasPrefix;
        $this->hasDate = $hasDate;
        $this->dateFormat = $dateFormat;
        $this->lengthSequence = $lengthSequence;
        $this->status = $status;
        $this->updatedBy = $updatedBy;
        $this->updatedAt = new DateTimeImmutable();
    }
}