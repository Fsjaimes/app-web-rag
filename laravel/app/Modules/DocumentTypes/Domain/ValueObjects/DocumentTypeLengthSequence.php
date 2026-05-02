<?php

declare(strict_types=1);

namespace App\Modules\DocumentTypes\Domain\ValueObjects;

final class DocumentTypeLengthSequence
{
    private const MIN_VALUE = 1;
    private const MAX_VALUE = 10;

    private function __construct(private int $value)
    {
    }

    public static function fromMixed(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || !ctype_digit($value)) {
                return null;
            }
            $value = (int) $value;
        }

        if (!is_int($value)) {
            return null;
        }

        if ($value < self::MIN_VALUE || $value > self::MAX_VALUE) {
            return null;
        }

        return new self($value);
    }

    public function toDatabaseValue(): int
    {
        return $this->value;
    }

    public function value(): int
    {
        return $this->value;
    }

    public function description(): string
    {
        return (string) $this->value;
    }

    public static function options(): array
    {
        $options = [];

        for ($value = self::MIN_VALUE; $value <= self::MAX_VALUE; $value++) {
            $options[] = [
                'id' => $value,
                'description' => (string) $value,
            ];
        }

        return $options;
    }

    public static function ids(): array
    {
        return array_column(self::options(), 'id');
    }
}
