<?php
declare(strict_types=1);

namespace App\Modules\Assistants\Domain\Repositories;

use App\Modules\Assistants\Domain\Entities\Assistant;

interface AssistantRepositoryInterface
{
    public function findById(string $id): ?Assistant;
    
    public function findAll(): array;
    
    public function save(Assistant $Assistant): void;
    
    public function delete(string $id): void;
}