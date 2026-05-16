<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Application\Handlers;

use App\Modules\Assistants\Application\Commands\AskQuestionCommand;
use App\Modules\Assistants\Application\DTOs\ChatResponseDTO;
use App\Modules\Assistants\Application\DTOs\MessageDTO;
use App\Modules\Assistants\Application\DTOs\AskQuestionDTO;
use App\Modules\Assistants\Domain\Entities\Conversation;
use App\Modules\Assistants\Domain\Entities\Message;
use App\Modules\Assistants\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Assistants\Domain\Repositories\MessageRepositoryInterface;
use App\Modules\Assistants\Infrastructure\Services\AIAssistantServiceInterface;
use Illuminate\Support\Str;

/**
 * Handler que procesa una pregunta del estudiante al asistente.
 *
 * Flujo completo:
 * 1. Busca o crea la conversación
 * 2. Guarda el mensaje del estudiante
 * 3. Recupera el historial para dar contexto al LLM
 * 4. Llama a FastAPI con la pregunta + historial
 * 5. Guarda la respuesta del asistente
 * 6. Devuelve el mensaje de respuesta como DTO
 */
final class AskQuestionHandler
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepository,
        private readonly MessageRepositoryInterface      $messageRepository,
        private readonly AIAssistantServiceInterface     $assistantService,
    ) {}

    public function handle(AskQuestionCommand $command): ChatResponseDTO
    {
        $dto = $command->dto;

        // 1. Buscar conversación existente o crear una nueva
        $conversation = $this->resolveConversation($dto);

        // 2. Guardar el mensaje del estudiante en PostgreSQL
        $userMessage = Message::create(
            uuid:           (string) Str::uuid(),
            conversationId: $this->getConversationId($conversation->uuid()),
            role:           'user',
            content:        $dto->question,
        );
        $this->messageRepository->save($userMessage);

        // 3. Recuperar historial de la conversación para contexto
        $conversationId = $this->getConversationId($conversation->uuid());
        $history = $this->messageRepository->findByConversationId($conversationId);

        // 4. Llamar a FastAPI — aquí ocurre el RAG completo
        $aiResponse = $this->assistantService->ask(
            question:       $dto->question,
            conversationId: $conversation->uuid(),
            history:        $history,
        );

        // 5. Guardar la respuesta del asistente con las fuentes usadas
        $assistantMessage = Message::create(
            uuid:           (string) Str::uuid(),
            conversationId: $conversationId,
            role:           'assistant',
            content:        $aiResponse['answer'],
            sources:        $aiResponse['sources'] ?? null,
        );
        $this->messageRepository->save($assistantMessage);

        // 6. Devolver el mensaje y el UUID de la conversación
        return new ChatResponseDTO(
            conversationUuid: $conversation->uuid(),
            message:          MessageDTO::fromEntity($assistantMessage),
        );
    }

    /**
     * Busca una conversación existente o crea una nueva.
     * Soporta usuarios autenticados y anónimos.
     */
    private function resolveConversation(
        AskQuestionDTO $dto
    ): Conversation {

        // Si viene un UUID de conversación, buscarla directamente
        if ($dto->conversationUuid !== null) {
            $conversation = $this->conversationRepository->findByUuid($dto->conversationUuid);
            if ($conversation !== null) {
                return $conversation;
            }
        }

        // Buscar conversación activa del usuario
        if ($dto->userId !== null) {
            $conversation = $this->conversationRepository->findByUserId($dto->userId);
            if ($conversation !== null) {
                return $conversation;
            }
        } else {
            // Usuario anónimo — buscar por session_id
            $conversation = $this->conversationRepository->findBySessionId($dto->sessionId);
            if ($conversation !== null) {
                return $conversation;
            }
        }

        // No existe — crear nueva conversación
        $newConversation = Conversation::create(
            uuid:      (string) Str::uuid(),
            userId:    $dto->userId,
            sessionId: $dto->userId === null ? $dto->sessionId : null,
        );

        return $this->conversationRepository->save($newConversation);
    }

    /**
     * Obtiene el ID interno (integer) de una conversación por su UUID.
     * Necesario para relacionar los mensajes en la BD.
     */
    private function getConversationId(string $uuid): int
    {
        return \App\Modules\Assistants\Infrastructure\Database\Models\ConversationModel
            ::where('uuid', $uuid)
            ->value('id');
    }
}