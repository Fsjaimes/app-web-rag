<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Domain\ValueObjects;

/**
 * Value Object que representa quién envió un mensaje en la conversación.
 *
 * user      → el estudiante que hace la pregunta
 * assistant → la respuesta generada por el LLM
 *
 * Este valor se envía directamente a la API del LLM como parte
 * del historial de conversación, por eso los valores coinciden
 * con el estándar de OpenAI/Gemini.
 */
final class MessageRole
{
    public const USER      = 'user';
    public const ASSISTANT = 'assistant';

    private const VALID_ROLES = [
        self::USER,
        self::ASSISTANT,
    ];

    private function __construct(
        private readonly string $value
    ) {}

    public static function from(string $value): self
    {
        if (!in_array($value, self::VALID_ROLES, strict: true)) {
            throw new \InvalidArgumentException(
                "Rol de mensaje inválido: '{$value}'. " .
                "Valores permitidos: " . implode(', ', self::VALID_ROLES)
            );
        }

        return new self($value);
    }

    public static function user(): self      { return new self(self::USER); }
    public static function assistant(): self { return new self(self::ASSISTANT); }

    public function value(): string { return $this->value; }

    public function isUser(): bool      { return $this->value === self::USER; }
    public function isAssistant(): bool { return $this->value === self::ASSISTANT; }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string { return $this->value; }
}