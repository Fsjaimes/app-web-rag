<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Domain\Repositories;

use App\Modules\AcademicDocuments\Domain\Entities\AcademicDocument;

/**
 * Contrato que define las operaciones de persistencia
 * para documentos académicos.
 *
 * El dominio solo conoce esta interfaz.
 * La implementación concreta (Eloquent) vive en Infrastructure
 * y se inyecta vía ServiceProvider.
 */
interface AcademicDocumentRepositoryInterface
{
    public function save(AcademicDocument $document): void;

    public function findByUuid(string $uuid): ?AcademicDocument;

    /** @return AcademicDocument[] */
    public function findAll(): array;

    public function delete(string $uuid): void;
}