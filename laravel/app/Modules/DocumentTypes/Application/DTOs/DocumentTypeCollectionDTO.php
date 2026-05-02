<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\DTOs;

use App\Modules\DocumentTypes\Domain\Entities\DocumentType;

final class DocumentTypeCollectionDTO
{
    public function __construct(
        public array $items,
        public int $total,
    ) {}

    public static function fromDomain(array $entities, int $total): self
    {
        $items = array_map(
            fn(DocumentType $entity) => DocumentTypeDTO::fromDomain($entity),
            $entities
        );

        return new self(
            items: $items,
            total: $total,
        );
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(
                fn(DocumentTypeDTO $dto) => [
                    'id' => $dto->id,
                    'uuid' => $dto->uuid,
                    'code' => $dto->prefix,
                    'name' => $dto->name,
                    'prefix' => $dto->prefix,
                    'inventoryMovementType' => $dto->inventoryMovementType?->description(),
                    'affectsInventory' => $dto->affectsInventory->description(),
                    'allowNegativeInventory' => $dto->allowNegativeInventory->description(),
                    'hasPrefix' => $dto->hasPrefix->description(),
                    'hasDate' => $dto->hasDate->description(),
                    'dateFormat' => $dto->dateFormat?->description(),
                    'lengthSequence' => $dto->lengthSequence->description(),
                    'status' => $dto->status->description(),
                ],
                $this->items
            ),
            'meta' => [
                'total' => $this->total,
            ],
        ];
    }
}
