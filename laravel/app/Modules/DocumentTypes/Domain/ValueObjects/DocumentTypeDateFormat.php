<?php

declare(strict_types=1);

namespace App\Modules\DocumentTypes\Domain\ValueObjects;

enum DocumentTypeDateFormat: string
{
    case Year = 'YYYY';
    case YearMonth = 'YYMM';
    case YearMonthDay = 'YYMMDD';

    public function id(): int
    {
        return match ($this) {
            self::Year => 1,
            self::YearMonth => 2,
            self::YearMonthDay => 3,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Year => 'AÑO (YYYY)',
            self::YearMonth => 'AÑO Y MES (YYMM)',
            self::YearMonthDay => 'AÑO, MES Y DÍA (YYMMDD)',
        };
    }

    public static function options(): array
    {
        return array_map(
            static fn (self $format): array => [
                'id' => $format->id(),
                'code' => $format->value,
                'description' => $format->description(),
            ],
            self::cases()
        );
    }

    public static function codes(): array
    {
        return array_map(static fn (self $format): string => $format->value, self::cases());
    }

    public static function fromMixed(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || (is_string($value) && ctype_digit(trim($value)))) {
            return match ((int) $value) {
                1 => self::Year,
                2 => self::YearMonth,
                3 => self::YearMonthDay,
                default => null,
            };
        }

        if (is_string($value)) {
            $normalized = strtoupper(trim($value));
            return self::tryFrom($normalized);
        }

        return null;
    }

    public function value(): ?string
    {
        return $this->value ?? null;
    }
}
