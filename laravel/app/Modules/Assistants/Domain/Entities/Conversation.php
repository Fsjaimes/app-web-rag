<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Domain\Entities;

/**
 * Entidad que representa una conversación entre un estudiante y el asistente.
 *
 * Una conversación agrupa todos los mensajes de una sesión.
 * Puede pertenecer a un usuario autenticado o ser anónima (solo session_id).
 */
final class Conversation
{
    /** @var Message[] */
    private array $messages = [];

    private function __construct(
        private readonly string             $uuid,
        private readonly ?int               $userId,
        private readonly ?string            $sessionId,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        string  $uuid,
        ?int    $userId,
        ?string $sessionId,
    ): self {
        return new self(
            uuid:      $uuid,
            userId:    $userId,
            sessionId: $sessionId,
            createdAt: new \DateTimeImmutable(),
        );
    }

    public static function reconstitute(
        string              $uuid,
        ?int                $userId,
        ?string             $sessionId,
        \DateTimeImmutable  $createdAt,
    ): self {
        return new self(
            uuid:      $uuid,
            userId:    $userId,
            sessionId: $sessionId,
            createdAt: $createdAt,
        );
    }

    public function uuid(): string              { return $this->uuid; }
    public function userId(): ?int              { return $this->userId; }
    public function sessionId(): ?string        { return $this->sessionId; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isAnonymous(): bool { return $this->userId === null; }
}