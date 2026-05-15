<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Application\DTOs;

use App\Modules\Assistants\Domain\Entities\Conversation;

/**
 * DTO que representa una conversación con sus mensajes incluidos.
 */
final class ConversationDTO
{
    /**
     * @param MessageDTO[] $messages
     */
    public function __construct(
        public readonly string  $uuid,
        public readonly ?int    $userId,
        public readonly ?string $sessionId,
        public readonly string  $createdAt,
        public readonly array   $messages = [],
    ) {}

    public static function fromEntity(Conversation $conversation, array $messages = []): self
    {
        return new self(
            uuid:      $conversation->uuid(),
            userId:    $conversation->userId(),
            sessionId: $conversation->sessionId(),
            createdAt: $conversation->createdAt()->format('Y-m-d H:i:s'),
            messages:  $messages,
        );
    }

    public function toArray(): array
    {
        return [
            'uuid'       => $this->uuid,
            'user_id'    => $this->userId,
            'session_id' => $this->sessionId,
            'created_at' => $this->createdAt,
            'messages'   => array_map(fn(MessageDTO $m) => $m->toArray(), $this->messages),
        ];
    }
}