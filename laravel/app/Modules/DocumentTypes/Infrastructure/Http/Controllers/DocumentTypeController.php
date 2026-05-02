<?php

declare(strict_types=1);

namespace App\Modules\DocumentTypes\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Modules\DocumentTypes\Infrastructure\Http\Requests\CreateDocumentTypeRequest;
use App\Modules\DocumentTypes\Infrastructure\Http\Requests\FilterDocumentTypeRequest;
use App\Modules\DocumentTypes\Infrastructure\Http\Requests\UpdateDocumentTypeRequest;
use App\Modules\DocumentTypes\Application\Commands\CreateDocumentTypeCommand;
use App\Modules\DocumentTypes\Application\Commands\DeleteDocumentTypeCommand;
use App\Modules\DocumentTypes\Application\Commands\UpdateDocumentTypeCommand;
use App\Modules\DocumentTypes\Application\Handlers\CreateDocumentTypeHandler;
use App\Modules\DocumentTypes\Application\Handlers\DeleteDocumentTypeHandler;
use App\Modules\DocumentTypes\Application\Handlers\GetDocumentTypeByUuidHandler;
use App\Modules\DocumentTypes\Application\Handlers\ListDocumentTypesHandler;
use App\Modules\DocumentTypes\Application\Handlers\UpdateDocumentTypeHandler;
use App\Modules\DocumentTypes\Application\Handlers\ValidateDocumentTypePrefixHandler;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeDateFormat;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeInventoryMovementType;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeLengthSequence;


class DocumentTypeController extends Controller
{
    public function index(
        FilterDocumentTypeRequest $r,
        ListDocumentTypesHandler $handler
    )
    {
        $filters = $r->validated();
        $collectionDTO = $handler->handle($filters);

        return Inertia::render('DocumentTypes/Index', [
            'documentTypes' => $collectionDTO->toArray(),
        ]);
    }

    public function viewCreate()
    {
        return Inertia::render('DocumentTypes/Create', [
            'serverDateFormats' => [
                'YYYY' => now()->format('Y'),
                'YYMM' => now()->format('ym'),
                'YYMMDD' => now()->format('ymd'),
            ],
            'datesFormats' => DocumentTypeDateFormat::options(),
            'inventoryMovementTypes' => DocumentTypeInventoryMovementType::options(),
            'lengthSequences' => DocumentTypeLengthSequence::options(),
        ]);
    }

    public function viewEdit(string $uuid, GetDocumentTypeByUuidHandler $handler)
    {
        $dto = $handler->handle($uuid);

        return Inertia::render('DocumentTypes/Edit', [
            'uuid' => $uuid,
            'documentTypeEdit' => $dto->toArray(),
            'serverDateFormats' => [
                'YYYY' => now()->format('Y'),
                'YYMM' => now()->format('ym'),
                'YYMMDD' => now()->format('ymd'),
            ],
            'datesFormats' => DocumentTypeDateFormat::options(),
            'inventoryMovementTypes' => DocumentTypeInventoryMovementType::options(),
            'lengthSequences' => DocumentTypeLengthSequence::options(),
        ]);
    }

    public function viewShow(string $uuid, GetDocumentTypeByUuidHandler $handler)
    {
        $dto = $handler->handle($uuid);

        return Inertia::render('DocumentTypes/Show', [
            'uuid' => $uuid,
            'item' => $dto->toArray(),
        ]);
    }

    public function store(CreateDocumentTypeRequest $r, CreateDocumentTypeHandler $handler)
    {
        $usersUuid = Auth::user()->uuid;
        $cmd = new CreateDocumentTypeCommand(
            prefix: $r->input('prefix'),
            name: $r->input('name'),
            affectsInventory: $r->input('affectsInventory'),
            inventoryMovementType: $r->filled('inventoryMovementType')
                ? (int) $r->input('inventoryMovementType')
                : null,
            allowNegativeInventory: $r->input('allowNegativeInventory'),
            hasPrefix: $r->input('hasPrefix'),
            hasDate: $r->input('hasDate'),
            dateFormat: $r->input('dateFormat'),
            lengthSequence: $r->filled('lengthSequence')
                ? (int) $r->input('lengthSequence')
                : null,
            status: $r->input('status'),
            actorUuid: $usersUuid,
        );

        $dto = $handler->handle($cmd);
        return response()->json(['data' => $dto->toArray()], 201);
    }

    public function update(string $uuid, UpdateDocumentTypeRequest $request, UpdateDocumentTypeHandler $handler)
    {
        $usersUuid = Auth::user()->uuid;

        $cmd = new UpdateDocumentTypeCommand(
            uuid: $uuid,
            prefix: $request->input('prefix'),
            name: $request->input('name'),
            affectsInventory: $request->input('affectsInventory'),
            inventoryMovementType: $request->filled('inventoryMovementType')
                ? (int) $request->input('inventoryMovementType')
                : null,
            allowNegativeInventory: $request->input('allowNegativeInventory'),
            hasPrefix: $request->input('hasPrefix'),
            hasDate: $request->input('hasDate'),
            dateFormat: $request->input('dateFormat'),
            lengthSequence: $request->filled('lengthSequence')
                ? (int) $request->input('lengthSequence')
                : null,
            status: $request->input('status'),
            actorUuid: $usersUuid,
        );

        $dto = $handler->handle($cmd);

        return response()->json(['data' => $dto->toArray()]);
    }

    public function validatePrefix(ValidateDocumentTypePrefixHandler $handler)
    {
        $validated = request()->validate([
            'prefix' => ['required', 'string', 'max:4'],
            'uuid' => ['nullable', 'string'],
        ]);

        $exists = $handler->handle(
            strtoupper(trim($validated['prefix'])),
            $validated['uuid'] ?? null
        );

        return response()->json(['exists' => $exists]);
    }

    public function destroy(string $uuid, DeleteDocumentTypeHandler $handler)
    {
        $handler->handle(new DeleteDocumentTypeCommand($uuid));

        return response()->json(['message' => 'Tipo de documento eliminado correctamente']);
    }
}