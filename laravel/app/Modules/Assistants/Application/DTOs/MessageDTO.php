<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Application\DTOs;

use App\Modules\Assistants\Domain\Entities\Message;

/**
 * DTO que representa un mensaje para ser consumido fuera del dominio.
 */
final class MessageDTO
{
    public function __construct(
        public readonly string  $uuid,
        public readonly string  $role,
        public readonly string  $content,
        public readonly ?array  $sources,
        public readonly string  $createdAt,
    ) {}

    public static function fromEntity(Message $message): self
    {
        return new self(
            uuid:      $message->uuid(),
            role:      $message->role()->value(),
            content:   $message->content(),
            sources:   $message->sources(),
            createdAt: $message->createdAt()->format('Y-m-d H:i:s'),
        );
    }

    public function toArray(): array
    {
        return [
            'uuid'       => $this->uuid,
            'role'       => $this->role,
            'content'    => $this->content,
            'sources'    => $this->sources,
            'created_at' => $this->createdAt,
        ];
    }
}