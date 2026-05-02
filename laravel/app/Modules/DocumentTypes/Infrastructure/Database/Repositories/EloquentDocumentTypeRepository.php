<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Infrastructure\Database\Repositories;

use App\Modules\DocumentTypes\Domain\Repositories\DocumentTypeRepositoryInterface;
use App\Modules\DocumentTypes\Domain\Entities\DocumentType as DocumentTypeEntity;
use App\Modules\DocumentTypes\Domain\ValueObjects\{DocumentTypeAffectsInventory, DocumentTypeHasPrefix, DocumentTypeHasDate, DocumentTypeStatus, DocumentTypeDateFormat, DocumentTypeInventoryMovementType, DocumentTypeAllowNegativeInventory, DocumentTypeLengthSequence};
use App\Modules\DocumentTypes\Infrastructure\Database\Models\DocumentType;
use Illuminate\Support\Facades\DB;

class EloquentDocumentTypeRepository implements DocumentTypeRepositoryInterface
{
    public function findByUuid(string $uuid): ?DocumentTypeEntity
    {
        $model = DocumentType::where('uuid', $uuid)->first();

        if (!$model) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findById(string $id): ?DocumentTypeEntity
    {
        $model = DocumentType::find($id);
        
        if (!$model) {
            return null;
        }
        
        return $this->toDomain($model);
    }

    public function findByPrefix(string $prefix): ?DocumentTypeEntity
    {
        $model = DocumentType::where('prefix', strtoupper(trim($prefix)))->first();

        if (!$model) {
            return null;
        }

        return $this->toDomain($model);
    }
    
    public function findAll(): array
    {
        return DocumentType::all()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }

    public function list(array $filters = []): array
    {
        $query = DocumentType::query();
        $allowedColumns = (new DocumentType())->getFillable();

        foreach ($filters as $field => $value) {
            if (!in_array($field, $allowedColumns, true)) {
                continue;
            }
            if ($value === '' || $value === null) {
                continue;
            }
            $query->where($field, $value);
        }

        $documentTypes = $query->get()->map(fn($model) => $this->toDomain($model))->all();

        return [
            'data' => $documentTypes,
            'total' => count($documentTypes),
        ];
    }

    public function prefixExists(string $prefix, ?string $excludeUuid = null): bool
    {
        $query = DocumentType::query()->where('prefix', $prefix);

        if ($excludeUuid !== null && $excludeUuid !== '') {
            $query->where('uuid', '!=', $excludeUuid);
        }

        return $query->exists();
    }
    
    public function save(DocumentTypeEntity $documentType): DocumentTypeEntity
    {
        return DB::transaction(function () use ($documentType) {
            $model = new DocumentType();
            $model->uuid = $documentType->uuid();
            $model->name = $documentType->name();
            $model->prefix = $documentType->prefix();
            $model->affects_inventory = $documentType->affectsInventory()->value();
            $model->inventory_movement_type = $documentType->inventoryMovementType()?->value();
            $model->allow_negative_inventory = $documentType->allowNegativeInventory()->value();
            $model->has_prefix = $documentType->hasPrefix()->value();
            $model->has_date = $documentType->hasDate()->value();
            $model->date_format = $documentType->dateFormat()?->value();
            $model->length_sequence = $documentType->lengthSequence()->value();
            $model->status = $documentType->status()->value();
            $model->created_by = $documentType->createdBy();
            $model->updated_by = $documentType->updatedBy();
            $model->created_at = now();
            $model->updated_at = now();
            $model->save();
            $model->refresh();
            return $this->toDomain($model);
        });
    }

    public function update(DocumentTypeEntity $documentType): void
    {
        $model = DocumentType::where('uuid', $documentType->uuid())->first();
        if (!$model) {
            return;
        }

        DB::transaction(function () use ($model, $documentType) {
            $model->name = $documentType->name();
            $model->prefix = $documentType->prefix();
            $model->affects_inventory = $documentType->affectsInventory()->value();
            $model->inventory_movement_type = $documentType->inventoryMovementType()?->value();
            $model->allow_negative_inventory = $documentType->allowNegativeInventory()->value();
            $model->has_prefix = $documentType->hasPrefix()->value();
            $model->has_date = $documentType->hasDate()->value();
            $model->date_format = $documentType->dateFormat()?->value();
            $model->length_sequence = $documentType->lengthSequence()->value();
            $model->status = $documentType->status()->value();
            $model->updated_by = $documentType->updatedBy();
            $model->updated_at = now();
            $model->save();
        });
    }
    
    public function delete(string $id): void
    {
        DocumentType::where('id', $id)->delete();
    }
    
    private function toDomain(DocumentType $model): DocumentTypeEntity
    {
        return new DocumentTypeEntity(
            id: (int) $model->id,
            uuid: $model->uuid,
            name: $model->name,
            prefix: $model->prefix,
            affectsInventory: DocumentTypeAffectsInventory::fromMixed($model->affects_inventory),
            inventoryMovementType: DocumentTypeInventoryMovementType::fromMixed($model->inventory_movement_type),
            allowNegativeInventory: DocumentTypeAllowNegativeInventory::fromMixed($model->allow_negative_inventory),
            hasPrefix: DocumentTypeHasPrefix::fromMixed($model->has_prefix),
            hasDate: DocumentTypeHasDate::fromMixed($model->has_date),
            dateFormat: DocumentTypeDateFormat::fromMixed($model->date_format),
            lengthSequence: DocumentTypeLengthSequence::fromMixed($model->length_sequence),
            status: DocumentTypeStatus::fromMixed($model->status),
            createdBy: $model->created_by,
            updatedBy: $model->updated_by,
            createdAt: new \DateTimeImmutable($model->created_at?->toAtomString() ?? 'now'),
            updatedAt: new \DateTimeImmutable($model->updated_at?->toAtomString() ?? 'now'),
            deletedAt: $model->deleted_at ? new \DateTimeImmutable($model->deleted_at->toAtomString()) : null,
        );
    }
}