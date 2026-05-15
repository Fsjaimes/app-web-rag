<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Domain\ValueObjects;

/**
 * Value Object que representa el identificador de sesión
 * para conversaciones de usuarios anónimos (sin cuenta).
 *
 * Permite agrupar los mensajes de un mismo visitante
 * sin requerir que esté autenticado.
 */
final class SessionId
{
    private function __construct(
        private readonly string $value
    ) {}

    /**
     * Crea un SessionId desde un string existente (ej: desde la sesión de Laravel).
     */
    public static function from(string $value): self
    {
        $value = trim($value);

        if (empty($value)) {
            throw new \InvalidArgumentException("El session_id no puede estar vacío.");
        }

        return new self($value);
    }

    /**
     * Genera un nuevo SessionId único.
     * Se usa cuando un visitante anónimo inicia su primera conversación.
     */
    public static function generate(): self
    {
        return new self((string) \Illuminate\Support\Str::uuid());
    }

    public function value(): string { return $this->value; }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string { return $this->value; }
}