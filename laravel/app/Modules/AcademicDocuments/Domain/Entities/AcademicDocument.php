<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Domain\Entities;

use App\Modules\AcademicDocuments\Domain\ValueObjects\DocumentStatus;
use App\Modules\AcademicDocuments\Domain\ValueObjects\DocumentTitle;

/**
 * Entidad que representa un documento académico de la UTS.
 *
 * Contiene la lógica de negocio del documento — no sabe nada
 * de bases de datos, ni de HTTP, ni de ChromaDB.
 * Esas responsabilidades viven en Infrastructure.
 */
final class AcademicDocument
{
    private function __construct(
        private readonly string        $uuid,
        private DocumentTitle          $title,
        private readonly string        $filename,
        private readonly string        $mimeType,
        private readonly int           $sizeBytes,
        private DocumentStatus         $status,
        private readonly int           $uploadedBy,
        private ?string                $errorMessage,
        private ?array                 $chromaIds,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    /**
     * Crea un nuevo documento en estado pendiente.
     * Se llama cuando el admin sube un PDF por primera vez.
     */
    public static function create(
        string $uuid,
        string $title,
        string $filename,
        string $mimeType,
        int    $sizeBytes,
        int    $uploadedBy,
    ): self {
        return new self(
            uuid:         $uuid,
            title:        DocumentTitle::from($title),
            filename:     $filename,
            mimeType:     $mimeType,
            sizeBytes:    $sizeBytes,
            status:       DocumentStatus::pending(),
            uploadedBy:   $uploadedBy,
            errorMessage: null,
            chromaIds:    null,
            createdAt:    new \DateTimeImmutable(),
        );
    }

    /**
     * Reconstruye una entidad desde datos persistidos (base de datos).
     */
    public static function reconstitute(
        string              $uuid,
        string              $title,
        string              $filename,
        string              $mimeType,
        int                 $sizeBytes,
        string              $status,
        int                 $uploadedBy,
        ?string             $errorMessage,
        ?array              $chromaIds,
        \DateTimeImmutable  $createdAt,
    ): self {
        return new self(
            uuid:         $uuid,
            title:        DocumentTitle::from($title),
            filename:     $filename,
            mimeType:     $mimeType,
            sizeBytes:    $sizeBytes,
            status:       DocumentStatus::from($status),
            uploadedBy:   $uploadedBy,
            errorMessage: $errorMessage,
            chromaIds:    $chromaIds,
            createdAt:    $createdAt,
        );
    }

    // ── Transiciones de estado ──────────────────────────────────────────────

    /**
     * Marca el documento como en proceso de indexación.
     * Lo llama el Handler justo antes de enviar el PDF a FastAPI.
     */
    public function markAsProcessing(): void
    {
        $this->status = DocumentStatus::processing();
        $this->errorMessage = null;
    }

    /**
     * Marca el documento como indexado exitosamente.
     * Lo llama el Handler cuando FastAPI confirma la indexación.
     *
     * @param string[] $chromaIds IDs de los chunks guardados en ChromaDB
     */
    public function markAsIndexed(array $chromaIds): void
    {
        $this->status    = DocumentStatus::indexed();
        $this->chromaIds = $chromaIds;
        $this->errorMessage = null;
    }

    /**
     * Marca el documento con error.
     * Lo llama el Handler cuando FastAPI devuelve un error.
     */
    public function markAsError(string $errorMessage): void
    {
        $this->status       = DocumentStatus::error();
        $this->errorMessage = $errorMessage;
    }

    // ── Getters ────────────────────────────────────────────────────────────

    public function uuid(): string          { return $this->uuid; }
    public function title(): DocumentTitle  { return $this->title; }
    public function filename(): string      { return $this->filename; }
    public function mimeType(): string      { return $this->mimeType; }
    public function sizeBytes(): int        { return $this->sizeBytes; }
    public function status(): DocumentStatus { return $this->status; }
    public function uploadedBy(): int       { return $this->uploadedBy; }
    public function errorMessage(): ?string { return $this->errorMessage; }
    public function chromaIds(): ?array     { return $this->chromaIds; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isIndexed(): bool { return $this->status->isIndexed(); }
    public function hasError(): bool  { return $this->status->isError(); }
}