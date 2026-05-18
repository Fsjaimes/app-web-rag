<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Infrastructure\Database\Repositories;

use App\Modules\Assistants\Domain\Entities\Message;
use App\Modules\Assistants\Domain\Repositories\MessageRepositoryInterface;
use App\Modules\Assistants\Infrastructure\Database\Models\MessageModel;

class EloquentMessageRepository implements MessageRepositoryInterface
{
    public function save(Message $message): void
    {
        $model = MessageModel::firstOrNew(['uuid' => $message->uuid()]);

        $model->uuid            = $message->uuid();
        $model->conversation_id = $message->conversationId();
        $model->role            = $message->role()->value();
        $model->content         = $message->content();
        $model->sources         = $message->sources();

        $model->save();
    }

    public function findByConversationId(int $conversationId): array
    {
        return MessageModel::where('conversation_id', $conversationId)
            ->oldest()
            ->get()
            ->map(fn(MessageModel $model) => $this->toDomain($model))
            ->all();
    }

    private function toDomain(MessageModel $model): Message
    {
        return Message::reconstitute(
            uuid:           $model->uuid,
            conversationId: (int) $model->conversation_id,
            role:           $model->role,
            content:        $model->content,
            sources:        $model->sources,
            createdAt:      new \DateTimeImmutable($model->created_at->toAtomString()),
        );
    }
}
