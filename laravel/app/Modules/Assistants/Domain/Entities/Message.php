<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Domain\Entities;

use App\Modules\Assistants\Domain\ValueObjects\MessageRole;

/**
 * Entidad que representa un mensaje dentro de una conversación.
 *
 * sources contiene los fragmentos de documentos que el RAG usó
 * para construir la respuesta — solo se llena en mensajes del asistente.
 */
final class Message
{
    private function __construct(
        private readonly string             $uuid,
        private readonly int                $conversationId,
        private readonly MessageRole        $role,
        private readonly string             $content,
        private readonly ?array             $sources,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        string      $uuid,
        int         $conversationId,
        string      $role,
        string      $content,
        ?array      $sources = null,
    ): self {
        return new self(
            uuid:           $uuid,
            conversationId: $conversationId,
            role:           MessageRole::from($role),
            content:        $content,
            sources:        $sources,
            createdAt:      new \DateTimeImmutable(),
        );
    }

    public static function reconstitute(
        string              $uuid,
        int                 $conversationId,
        string              $role,
        string              $content,
        ?array              $sources,
        \DateTimeImmutable  $createdAt,
    ): self {
        return new self(
            uuid:           $uuid,
            conversationId: $conversationId,
            role:           MessageRole::from($role),
            content:        $content,
            sources:        $sources,
            createdAt:      $createdAt,
        );
    }

    public function uuid(): string               { return $this->uuid; }
    public function conversationId(): int        { return $this->conversationId; }
    public function role(): MessageRole          { return $this->role; }
    public function content(): string            { return $this->content; }
    public function sources(): ?array            { return $this->sources; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isFromUser(): bool      { return $this->role->isUser(); }
    public function isFromAssistant(): bool { return $this->role->isAssistant(); }
}