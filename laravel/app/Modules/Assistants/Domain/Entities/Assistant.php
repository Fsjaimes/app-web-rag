<?php
declare(strict_types=1);

namespace App\Modules\Assistants\Domain\Entities;

final class Assistant
{
    private function __construct(
        private string $id,
        // TODO: Agregar propiedades de la entidad
    ) {}
    
    public static function create(string $id): self
    {
        return new self($id);
    }
    
    public function id(): string
    {
        return $this->id;
    }
    
    // TODO: Agregar métodos de la entidad
}