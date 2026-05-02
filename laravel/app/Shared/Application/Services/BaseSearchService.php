<?php

declare(strict_types=1);

namespace App\Shared\Application\Services;

use App\Shared\Domain\Repositories\SearchRepositoryInterface;

final class BaseSearchService
{
    public function __construct(
        private ?SearchRepositoryInterface $repository
    ) {}

    
    public function execute(
        string $query,
        array $fields,
        array $selectFields,
        int $limit,
        array $filters = []
    ): array {
        if (!$this->repository) {
            return [];
        }
        return $this->repository->search(
            query: $query,
            fields: $fields,
            limit: $limit,
            filters: $filters,
            selectFields: $selectFields
        );
    }
}