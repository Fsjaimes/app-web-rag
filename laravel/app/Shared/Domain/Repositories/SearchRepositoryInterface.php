<?php

declare(strict_types=1);

namespace App\Shared\Domain\Repositories;

interface SearchRepositoryInterface
{
    public function search(
        string $query,
        array $fields,
        int $limit,
        array $selectFields = [],
        array $filters = []
    ): array;
}
