<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Infrastructure\Database\Repositories;

use App\Modules\AcademicDocuments\Domain\Entities\AcademicDocument as AcademicDocumentEntity;
use App\Modules\AcademicDocuments\Domain\Repositories\AcademicDocumentRepositoryInterface;
use App\Modules\AcademicDocuments\Infrastructure\Database\Models\AcademicDocument;

class EloquentAcademicDocumentRepository implements AcademicDocumentRepositoryInterface
{
    public function save(AcademicDocumentEntity $document): void
    {
        $model = AcademicDocument::firstOrNew(['uuid' => $document->uuid()]);

        $model->uuid          = $document->uuid();
        $model->title         = $document->title()->value();
        $model->filename      = $document->filename();
        $model->mime_type     = $document->mimeType();
        $model->size_bytes    = $document->sizeBytes();
        $model->status        = $document->status()->value();
        $model->error_message = $document->errorMessage();
        $model->chroma_ids    = $document->chromaIds();
        $model->uploaded_by   = $document->uploadedBy();

        $model->save();
    }

    public function findByUuid(string $uuid): ?AcademicDocumentEntity
    {
        $model = AcademicDocument::where('uuid', $uuid)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(): array
    {
        return AcademicDocument::all()
            ->map(fn(AcademicDocument $model) => $this->toDomain($model))
            ->all();
    }

    public function delete(string $uuid): void
    {
        AcademicDocument::where('uuid', $uuid)->delete();
    }

    private function toDomain(AcademicDocument $model): AcademicDocumentEntity
    {
        return AcademicDocumentEntity::reconstitute(
            uuid:         $model->uuid,
            title:        $model->title,
            filename:     $model->filename,
            mimeType:     $model->mime_type,
            sizeBytes:    (int) $model->size_bytes,
            status:       $model->status,
            uploadedBy:   (int) $model->uploaded_by,
            errorMessage: $model->error_message,
            chromaIds:    $model->chroma_ids,
            createdAt:    new \DateTimeImmutable($model->created_at->toAtomString()),
        );
    }
}
