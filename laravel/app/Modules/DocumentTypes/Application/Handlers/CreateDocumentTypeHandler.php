<?php
namespace App\Modules\DocumentTypes\Application\Handlers;

use App\Modules\DocumentTypes\Application\Commands\CreateDocumentTypeCommand;
use App\Modules\DocumentTypes\Application\DTOs\SaveDocumentTypeDTO;
use App\Modules\DocumentTypes\Domain\Repositories\DocumentTypeRepositoryInterface;
use App\Modules\DocumentTypes\Domain\ValueObjects\{DocumentTypeStatus, DocumentTypeAffectsInventory, DocumentTypeHasPrefix, DocumentTypeHasDate, DocumentTypeDateFormat, DocumentTypeInventoryMovementType, DocumentTypeAllowNegativeInventory, DocumentTypeLengthSequence};
use App\Modules\DocumentTypes\Domain\Entities\DocumentType;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateDocumentTypeHandler {
    public function __construct(private DocumentTypeRepositoryInterface $repo) {}
    public function handle(CreateDocumentTypeCommand $c): SaveDocumentTypeDTO {
        if ($this->repo->prefixExists($c->prefix)) {
            throw ValidationException::withMessages([
                'prefix' => 'El prefijo ya existe',
            ]);
        }

        $uuid = (string) Str::ulid();
        $documentType = DocumentType::create(
            id: null,
            uuid: $uuid,
            name: $c->name,
            prefix: $c->prefix,
            affectsInventory: DocumentTypeAffectsInventory::fromMixed($c->affectsInventory),
            inventoryMovementType: DocumentTypeInventoryMovementType::fromMixed($c->inventoryMovementType),
            allowNegativeInventory: DocumentTypeAllowNegativeInventory::fromMixed($c->allowNegativeInventory),
            hasPrefix: DocumentTypeHasPrefix::fromMixed($c->hasPrefix),
            hasDate: DocumentTypeHasDate::fromMixed($c->hasDate),
            dateFormat: DocumentTypeDateFormat::fromMixed($c->dateFormat),
            lengthSequence: DocumentTypeLengthSequence::fromMixed($c->lengthSequence),
            status: DocumentTypeStatus::fromMixed($c->status),
            createdBy: $c->actorUuid,
        );
        $saved = $this->repo->save($documentType);
        return SaveDocumentTypeDTO::fromDomain($saved);
    }
}
