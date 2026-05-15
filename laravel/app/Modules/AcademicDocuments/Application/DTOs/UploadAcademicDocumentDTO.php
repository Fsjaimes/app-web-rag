<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Application\DTOs;

/**
 * DTO que transporta los datos necesarios para subir un documento.
 * Lo construye el Controller desde el Request validado
 * y lo pasa al Command.
 */
final class UploadAcademicDocumentDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $filename,
        public readonly string $mimeType,
        public readonly int    $sizeBytes,
        public readonly int    $uploadedBy,
        public readonly string $filePath,   // ruta temporal del archivo subido
    ) {}
}