<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Domain\ValueObjects;

/**
 * Value Object que representa el estado de un documento académico
 * dentro del pipeline de indexación RAG.
 *
 * Es inmutable — una vez creado no cambia.
 * Para "cambiar" el estado, se crea una nueva instancia.
 */
final class DocumentStatus
{
    // Estados posibles del ciclo de vida de un documento
    public const PENDING    = 'pending';    // recién subido, sin procesar
    public const PROCESSING = 'processing'; // FastAPI está indexando
    public const INDEXED    = 'indexed';    // disponible para consultas RAG
    public const ERROR      = 'error';      // falló la indexación

    private const VALID_STATUSES = [
        self::PENDING,
        self::PROCESSING,
        self::INDEXED,
        self::ERROR,
    ];

    private function __construct(
        private readonly string $value
    ) {}

    /**
     * Factory method — única forma de crear un DocumentStatus.
     * Lanza excepción si el valor no es válido.
     */
    public static function from(string $value): self
    {
        if (!in_array($value, self::VALID_STATUSES, strict: true)) {
            throw new \InvalidArgumentException(
                "Estado de documento inválido: '{$value}'. " .
                "Valores permitidos: " . implode(', ', self::VALID_STATUSES)
            );
        }

        return new self($value);
    }

    // Factories semánticas — hacen el código más legible
    public static function pending(): self    { return new self(self::PENDING); }
    public static function processing(): self { return new self(self::PROCESSING); }
    public static function indexed(): self    { return new self(self::INDEXED); }
    public static function error(): self      { return new self(self::ERROR); }

    public function value(): string { return $this->value; }

    public function isPending(): bool    { return $this->value === self::PENDING; }
    public function isProcessing(): bool { return $this->value === self::PROCESSING; }
    public function isIndexed(): bool    { return $this->value === self::INDEXED; }
    public function isError(): bool      { return $this->value === self::ERROR; }

    /**
     * Dos Value Objects son iguales si tienen el mismo valor.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string { return $this->value; }
}