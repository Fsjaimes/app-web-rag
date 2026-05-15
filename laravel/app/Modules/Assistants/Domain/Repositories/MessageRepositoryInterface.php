<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Domain\Repositories;

use App\Modules\Assistants\Domain\Entities\Message;

interface MessageRepositoryInterface
{
    public function save(Message $message): void;

    /** @return Message[] */
    public function findByConversationId(int $conversationId): array;
}