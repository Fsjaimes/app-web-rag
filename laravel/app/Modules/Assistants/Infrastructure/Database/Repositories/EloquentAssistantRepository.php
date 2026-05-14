<?php
declare(strict_types=1);

namespace App\Modules\Assistants\Infrastructure\Database\Repositories;

use App\Modules\Assistants\Domain\Repositories\AssistantRepositoryInterface;
use App\Modules\Assistants\Domain\Entities\Assistant;
use App\Modules\Assistants\Infrastructure\Database\Models\Assistant;

class EloquentAssistantRepository implements AssistantRepositoryInterface
{
    public function findById(string $id): ?Assistant
    {
        $model = Assistant::find($id);
        
        if (!$model) {
            return null;
        }
        
        return $this->toDomain($model);
    }
    
    public function findAll(): array
    {
        return Assistant::all()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }
    
    public function save(Assistant $Assistant): void
    {
        $model = Assistant::find($Assistant->id()) ?? new Assistant();
        
        // TODO: Mapear propiedades de la entidad al modelo
        // $model->name = $Assistant->name();
        
        $model->save();
    }
    
    public function delete(string $id): void
    {
        Assistant::destroy($id);
    }
    
    private function toDomain(Assistant $model): Assistant
    {
        // TODO: Implementar conversión de Model a Entity
        throw new \RuntimeException('Implementar conversión de Model a Entity en toDomain()');
    }
}