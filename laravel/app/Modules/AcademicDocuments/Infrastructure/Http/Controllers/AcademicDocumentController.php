<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AcademicDocuments\Application\Commands\DeleteAcademicDocumentCommand;
use App\Modules\AcademicDocuments\Application\Commands\UploadAcademicDocumentCommand;
use App\Modules\AcademicDocuments\Application\DTOs\UploadAcademicDocumentDTO;
use App\Modules\AcademicDocuments\Application\Handlers\DeleteAcademicDocumentHandler;
use App\Modules\AcademicDocuments\Application\Handlers\ListAcademicDocumentsHandler;
use App\Modules\AcademicDocuments\Application\Handlers\UploadAcademicDocumentHandler;
use App\Modules\AcademicDocuments\Infrastructure\Http\Requests\UploadAcademicDocumentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AcademicDocumentController extends Controller
{
    public function __construct(
        private readonly ListAcademicDocumentsHandler  $listHandler,
        private readonly UploadAcademicDocumentHandler $uploadHandler,
        private readonly DeleteAcademicDocumentHandler $deleteHandler,
    ) {}

    /**
     * Lista todos los documentos académicos en el panel de administración.
     *
     * GET /documentos-academicos
     */
    public function index(): Response
    {
        $documents = $this->listHandler->handle();

        return Inertia::render('AcademicDocuments/Index', [
            'items' => array_map(fn($doc) => $doc->toArray(), $documents),
        ]);
    }

    /**
     * Sube un PDF y lo envía a indexar en ChromaDB vía FastAPI.
     *
     * POST /documentos-academicos
     * Body: { title: string, file: UploadedFile (PDF) }
     */
    public function store(UploadAcademicDocumentRequest $request): JsonResponse
    {
        $file = $request->file('file');

        // Guardar el PDF en storage/app/documents/{uuid}.pdf
        $uuid     = (string) Str::uuid();
        $filename = $file->getClientOriginalName();
        $stored   = $file->storeAs('documents', $uuid . '.pdf', 'local');

        $dto = new UploadAcademicDocumentDTO(
            title:      $request->input('title'),
            filename:   $filename,
            mimeType:   $file->getMimeType() ?? 'application/pdf',
            sizeBytes:  $file->getSize(),
            uploadedBy: auth()->id(),
            filePath:   storage_path('app/' . $stored),
        );

        $documentDTO = $this->uploadHandler->handle(new UploadAcademicDocumentCommand($dto));

        return response()->json($documentDTO->toArray(), 201);
    }

    /**
     * Elimina un documento de PostgreSQL y sus vectores de ChromaDB.
     *
     * DELETE /documentos-academicos/{uuid}
     */
    public function destroy(string $uuid): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteAcademicDocumentCommand($uuid));

        return response()->json(null, 204);
    }
}
