<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Domain\Repositories;

use App\Modules\DocumentTypes\Domain\Entities\DocumentType;

interface DocumentTypeRepositoryInterface
{
    public function findByUuid(string $uuid): ?DocumentType;

    public function findById(string $id): ?DocumentType;

    public function findByPrefix(string $prefix): ?DocumentType;
    
    public function findAll(): array;

    public function list(array $filters = []): array;

    public function prefixExists(string $prefix, ?string $excludeUuid = null): bool;
    
    public function save(DocumentType $documentType): DocumentType;

    public function update(DocumentType $documentType): void;
    
    public function delete(string $id): void;
}