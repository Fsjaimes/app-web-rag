<?php
declare(strict_types=1);

namespace App\Modules\DocumentTypes\Domain\ValueObjects;

enum DocumentTypeAffectsInventory: string
{
    case Yes = '1';
    case No = '0';

    public static function fromMixed(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['1', 'true', 't', 'yes', 'y'], true)
                ? self::Yes
                : self::No;
        }

        if (is_bool($value)) {
            return $value ? self::Yes : self::No;
        }

        if (is_int($value) || is_float($value)) {
            return ((int) $value) === 1 ? self::Yes : self::No;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? self::Yes : self::No;
    }

    public function description(): string
    {
        return match ($this) {
            self::Yes => 'SI',
            self::No => 'NO',
        };
    }

    public function value(): string
    {
        return $this->value;
    }

    public function toBool(): bool
    {
        return $this === self::Yes;
    }
}