<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Database\Repositories;

abstract class BaseSearchRepository
{
    abstract protected function model(): string;

    public function search(
        string $query,
        array $fields,
        int $limit,
        array $selectFields = [],
        array $filters = []
    ): array {
        $modelClass = $this->model();

        $builder = $modelClass::query();
        $driver = $builder->getConnection()->getDriverName();
        foreach ($filters as $column => $value) {
            if (! is_string($column) || ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            if (is_array($value)) {
                $builder->whereIn($column, $value);
            } else {
                $builder->where($column, $value);
            }
        }

        if ($query !== '' && ! empty($fields)) {
            $builder->where(function ($q) use ($query, $fields, $driver) {
                $likePattern = is_numeric($query)
                    ? $query . '%'
                    : '%' . $query . '%';

                $first = true;
                foreach ($fields as $field) {
                    $column = $q->qualifyColumn($field);

                    if ($driver === 'pgsql') {
                        $sql = "{$column}::text ILIKE ?";
                        if ($first) {
                            $q->whereRaw($sql, [$likePattern]);
                            $first = false;
                        } else {
                            $q->orWhereRaw($sql, [$likePattern]);
                        }

                        continue;
                    }

                    $castExpr = match ($driver) {
                        'sqlite' => "CAST({$column} AS TEXT)",
                        default => "CAST({$column} AS CHAR(255))",
                    };
                    $sql = "LOWER({$castExpr}) LIKE ?";
                    if ($first) {
                        $q->whereRaw($sql, [mb_strtolower($likePattern, 'UTF-8')]);
                        $first = false;
                    } else {
                        $q->orWhereRaw($sql, [mb_strtolower($likePattern, 'UTF-8')]);
                    }
                }
            });
        }

        // No forzar `uuid`: en algunos esquemas `shd_products` no tiene esa columna y el SELECT falla.
        $select = array_unique(array_merge(['id'], $selectFields));

        $results = $builder
            ->select($select)
            ->limit($limit)
            ->get();

        return [
            'data' => $results->toArray(),
            'total' => $results->count(),
        ];
    }
}
