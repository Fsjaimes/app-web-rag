<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Domain\Repositories;

use App\Modules\Assistant\Domain\Entities\Conversation;

interface ConversationRepositoryInterface
{
    public function save(Conversation $conversation): Conversation;

    public function findByUuid(string $uuid): ?Conversation;

    /**
     * Busca la conversación activa de un usuario autenticado.
     * Se usa para continuar una conversación existente.
     */
    public function findByUserId(int $userId): ?Conversation;

    /**
     * Busca la conversación activa de un usuario anónimo.
     */
    public function findBySessionId(string $sessionId): ?Conversation;
}