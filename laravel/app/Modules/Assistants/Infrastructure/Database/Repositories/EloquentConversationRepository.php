<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Infrastructure\Database\Repositories;

use App\Modules\Assistants\Domain\Entities\Conversation;
use App\Modules\Assistants\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Assistants\Infrastructure\Database\Models\ConversationModel;

class EloquentConversationRepository implements ConversationRepositoryInterface
{
    public function save(Conversation $conversation): Conversation
    {
        $model = ConversationModel::firstOrNew(['uuid' => $conversation->uuid()]);

        $model->uuid       = $conversation->uuid();
        $model->user_id    = $conversation->userId();
        $model->session_id = $conversation->sessionId();

        $model->save();

        return $this->toDomain($model);
    }

    public function findByUuid(string $uuid): ?Conversation
    {
        $model = ConversationModel::where('uuid', $uuid)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUserId(int $userId): ?Conversation
    {
        $model = ConversationModel::where('user_id', $userId)
            ->latest()
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findBySessionId(string $sessionId): ?Conversation
    {
        $model = ConversationModel::where('session_id', $sessionId)
            ->latest()
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(ConversationModel $model): Conversation
    {
        return Conversation::reconstitute(
            uuid:      $model->uuid,
            userId:    $model->user_id ? (int) $model->user_id : null,
            sessionId: $model->session_id,
            createdAt: new \DateTimeImmutable($model->created_at->toAtomString()),
        );
    }
}
