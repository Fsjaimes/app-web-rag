<?php

declare(strict_types=1);

namespace App\Modules\DocumentTypes\Domain\ValueObjects;

enum DocumentTypeInventoryMovementType: int
{
    case Entry = 1;
    case Exit = 2;
    case Both = 3;

    public function description(): string
    {
        return match ($this) {
            self::Entry => 'ENTRADA',
            self::Exit => 'SALIDA',
            self::Both => 'AMBOS',
        };
    }

    public static function options(): array
    {
        return array_map(
            static fn (self $movement): array => [
                'id' => $movement->value,
                'description' => $movement->description(),
            ],
            self::cases()
        );
    }

    public static function ids(): array
    {
        return array_map(static fn (self $movement): int => $movement->value, self::cases());
    }

    public static function fromMixed(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return self::tryFrom($value);
        }

        if (is_string($value)) {
            $normalized = trim($value);

            if (ctype_digit($normalized)) {
                return self::tryFrom((int) $normalized);
            }

            $lower = strtolower($normalized);
            return match ($lower) {
                'entrada', 'entry' => self::Entry,
                'salida', 'exit' => self::Exit,
                'ambos', 'both' => self::Both,
                default => null,
            };
        }

        return null;
    }

    public function value(): ?int
    {
        return $this->value ?? null;
    }
}
