<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Application\Handlers;

use App\Modules\DocumentTypes\Application\Commands\UpdateDocumentTypeCommand;
use App\Modules\DocumentTypes\Application\DTOs\SaveDocumentTypeDTO;
use App\Modules\DocumentTypes\Domain\Exceptions\DocumentTypeNotFoundException;
use App\Modules\DocumentTypes\Domain\Repositories\DocumentTypeRepositoryInterface;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeAffectsInventory;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeAllowNegativeInventory;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeDateFormat;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeHasDate;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeHasPrefix;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeInventoryMovementType;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeLengthSequence;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeStatus;
use Illuminate\Validation\ValidationException;

final class UpdateDocumentTypeHandler
{
    public function __construct(private DocumentTypeRepositoryInterface $repo) {}

    public function handle(UpdateDocumentTypeCommand $command): SaveDocumentTypeDTO
    {
        $documentType = $this->repo->findByUuid($command->uuid);
        if ($documentType === null) {
            throw DocumentTypeNotFoundException::withId($command->uuid);
        }

        if ($this->repo->prefixExists($command->prefix, $command->uuid)) {
            throw ValidationException::withMessages([
                'prefix' => 'El prefijo ya existe',
            ]);
        }

        $documentType->update(
            name: $command->name,
            prefix: $command->prefix,
            affectsInventory: DocumentTypeAffectsInventory::fromMixed($command->affectsInventory),
            inventoryMovementType: DocumentTypeInventoryMovementType::fromMixed($command->inventoryMovementType),
            allowNegativeInventory: DocumentTypeAllowNegativeInventory::fromMixed($command->allowNegativeInventory),
            hasPrefix: DocumentTypeHasPrefix::fromMixed($command->hasPrefix),
            hasDate: DocumentTypeHasDate::fromMixed($command->hasDate),
            dateFormat: DocumentTypeDateFormat::fromMixed($command->dateFormat),
            lengthSequence: DocumentTypeLengthSequence::fromMixed($command->lengthSequence),
            status: DocumentTypeStatus::fromMixed($command->status),
            updatedBy: $command->actorUuid,
        );

        $this->repo->update($documentType);

        $fresh = $this->repo->findByUuid($command->uuid);
        if ($fresh === null) {
            throw DocumentTypeNotFoundException::withId($command->uuid);
        }

        return SaveDocumentTypeDTO::fromDomain($fresh);
    }
}
