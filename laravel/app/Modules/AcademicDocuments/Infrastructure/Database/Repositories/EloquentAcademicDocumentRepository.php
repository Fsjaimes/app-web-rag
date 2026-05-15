<?php
declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Infrastructure\Database\Repositories;

use App\Modules\AcademicDocuments\Domain\Repositories\AcademicDocumentRepositoryInterface;
use App\Modules\AcademicDocuments\Domain\Entities\AcademicDocument;
use App\Modules\AcademicDocuments\Infrastructure\Database\Models\AcademicDocument;

class EloquentAcademicDocumentRepository implements AcademicDocumentRepositoryInterface
{
    public function findById(string $id): ?AcademicDocument
    {
        $model = AcademicDocument::find($id);
        
        if (!$model) {
            return null;
        }
        
        return $this->toDomain($model);
    }
    
    public function findAll(): array
    {
        return AcademicDocument::all()
            ->map(fn($model) => $this->toDomain($model))
            ->toArray();
    }
    
    public function save(AcademicDocument $AcademicDocument): void
    {
        $model = AcademicDocument::find($AcademicDocument->id()) ?? new AcademicDocument();
        
        // TODO: Mapear propiedades de la entidad al modelo
        // $model->name = $AcademicDocument->name();
        
        $model->save();
    }
    
    public function delete(string $id): void
    {
        AcademicDocument::destroy($id);
    }
    
    private function toDomain(AcademicDocument $model): AcademicDocument
    {
        // TODO: Implementar conversión de Model a Entity
        throw new \RuntimeException('Implementar conversión de Model a Entity en toDomain()');
    }
}