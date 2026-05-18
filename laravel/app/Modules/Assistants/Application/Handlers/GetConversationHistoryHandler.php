<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Application\Handlers;

use App\Modules\Assistants\Application\DTOs\ConversationDTO;
use App\Modules\Assistants\Application\DTOs\MessageDTO;
use App\Modules\Assistants\Infrastructure\Database\Models\ConversationModel;
use App\Modules\Assistants\Infrastructure\Database\Models\MessageModel;
use App\Modules\Assistants\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Assistants\Domain\Repositories\MessageRepositoryInterface;

/**
 * Handler que recupera el historial completo de una conversación.
 * Lo usa el frontend para cargar mensajes previos al abrir el chat.
 */
final class GetConversationHistoryHandler
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepository,
        private readonly MessageRepositoryInterface      $messageRepository,
    ) {}

    public function handle(string $conversationUuid): ?ConversationDTO
    {
        $conversation = $this->conversationRepository->findByUuid($conversationUuid);

        if ($conversation === null) {
            return null;
        }

        // Obtener ID interno para buscar mensajes
        $conversationId = ConversationModel::where('uuid', $conversationUuid)->value('id');

        $messages = $this->messageRepository->findByConversationId($conversationId);

        // Convertir mensajes a DTOs
        $messageDTOs = array_map(
            fn($message) => MessageDTO::fromEntity($message),
            $messages
        );

        return ConversationDTO::fromEntity($conversation, $messageDTOs);
    }
}