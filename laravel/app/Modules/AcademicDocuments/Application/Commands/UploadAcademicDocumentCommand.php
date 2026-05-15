<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Application\Commands;

use App\Modules\AcademicDocuments\Application\DTOs\UploadAcademicDocumentDTO;

/**
 * Command que representa la intención de subir un documento académico.
 *
 * En DDD un Command es inmutable — solo transporta datos,
 * no ejecuta lógica. El Handler es quien actúa.
 */
final class UploadAcademicDocumentCommand
{
    public function __construct(
        public readonly UploadAcademicDocumentDTO $dto
    ) {}
}