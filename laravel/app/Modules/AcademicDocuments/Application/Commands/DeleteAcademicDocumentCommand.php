<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Application\Commands;

/**
 * Command que representa la intención de eliminar un documento académico.
 * Incluye eliminarlo de PostgreSQL y de ChromaDB.
 */
final class DeleteAcademicDocumentCommand
{
    public function __construct(
        public readonly string $uuid // identificador del documento a eliminar
    ) {}
}