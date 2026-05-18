<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Application\DTOs;

/**
 * DTO que empaqueta la respuesta completa del chat:
 * el UUID de la conversación (para que el frontend lo persista)
 * y el mensaje del asistente.
 */
final class ChatResponseDTO
{
    public function __construct(
        public readonly string     $conversationUuid,
        public readonly MessageDTO $message,
    ) {}

    public function toArray(): array
    {
        return [
            'conversation_uuid' => $this->conversationUuid,
            'message'           => $this->message->toArray(),
        ];
    }
}
