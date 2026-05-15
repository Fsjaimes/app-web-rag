<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Application\DTOs;

use App\Modules\AcademicDocuments\Domain\Entities\AcademicDocument;

/**
 * DTO que representa un documento académico para ser consumido
 * por la capa de Infrastructure (controladores, vistas, respuestas JSON).
 *
 * Convierte la entidad del dominio en un objeto simple y serializable.
 * Los controladores nunca reciben entidades del dominio directamente.
 */
final class AcademicDocumentDTO
{
    public function __construct(
        public readonly string  $uuid,
        public readonly string  $title,
        public readonly string  $filename,
        public readonly string  $mimeType,
        public readonly int     $sizeBytes,
        public readonly string  $status,
        public readonly int     $uploadedBy,
        public readonly ?string $errorMessage,
        public readonly ?array  $chromaIds,
        public readonly string  $createdAt,
    ) {}

    /**
     * Construye el DTO desde una entidad del dominio.
     * Este es el único punto donde la entidad se "traduce" hacia afuera.
     */
    public static function fromEntity(AcademicDocument $document): self
    {
        return new self(
            uuid:         $document->uuid(),
            title:        $document->title()->value(),
            filename:     $document->filename(),
            mimeType:     $document->mimeType(),
            sizeBytes:    $document->sizeBytes(),
            status:       $document->status()->value(),
            uploadedBy:   $document->uploadedBy(),
            errorMessage: $document->errorMessage(),
            chromaIds:    $document->chromaIds(),
            createdAt:    $document->createdAt()->format('Y-m-d H:i:s'),
        );
    }

    /**
     * Serializa el DTO a array — útil para respuestas JSON en la API.
     */
    public function toArray(): array
    {
        return [
            'uuid'          => $this->uuid,
            'title'         => $this->title,
            'filename'      => $this->filename,
            'mime_type'     => $this->mimeType,
            'size_bytes'    => $this->sizeBytes,
            'status'        => $this->status,
            'uploaded_by'   => $this->uploadedBy,
            'error_message' => $this->errorMessage,
            'chroma_ids'    => $this->chromaIds,
            'created_at'    => $this->createdAt,
        ];
    }
}