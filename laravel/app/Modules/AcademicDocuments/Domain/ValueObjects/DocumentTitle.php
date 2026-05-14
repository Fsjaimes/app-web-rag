<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Domain\ValueObjects;

/**
 * Value Object que representa el título de un documento académico.
 *
 * Encapsula las reglas de validación del título — si cambian,
 * solo se cambia aquí, no en diez lugares distintos.
 */
final class DocumentTitle
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 255;

    private function __construct(
        private readonly string $value
    ) {}

    public static function from(string $value): self
    {
        $value = trim($value);

        if (strlen($value) < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(
                "El título debe tener al menos " . self::MIN_LENGTH . " caracteres."
            );
        }

        if (strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                "El título no puede superar " . self::MAX_LENGTH . " caracteres."
            );
        }

        return new self($value);
    }

    public function value(): string { return $this->value; }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string { return $this->value; }
}