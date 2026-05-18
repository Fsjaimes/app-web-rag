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

        // 2. Recuperar historial ANTES de guardar el mensaje actual
        //    Solo mensajes válidos (pares user/assistant): así garantizamos
        //    alternancia correcta aunque haya fallos previos en la BD.
        $conversationId = $this->getConversationId($conversation->uuid());
        $history = $this->buildAlternatingHistory(
            $this->messageRepository->findByConversationId($conversationId)
        );

        // 3. Llamar a FastAPI PRIMERO — aquí ocurre el RAG completo.
        //    Solo guardamos en BD si la llamada es exitosa,
        //    para no dejar el historial en estado inconsistente.
        $aiResponse = $this->assistantService->ask(
            question:       $dto->question,
            conversationId: $conversation->uuid(),
            history:        $history,
        );

        // 4. Guardar el mensaje del estudiante y la respuesta del asistente
        $userMessage = Message::create(
            uuid:           (string) Str::uuid(),
            conversationId: $conversationId,
            role:           'user',
            content:        $dto->question,
        );
        $this->messageRepository->save($userMessage);

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

    /**
     * Filtra el historial para garantizar alternancia estricta user/assistant.
     *
     * Los modelos como Gemma requieren que los roles alternen sin repetición.
     * Si hay mensajes consecutivos del mismo rol (p.ej. por fallos previos),
     * los descarta para enviar al LLM solo pares válidos.
     *
     * @param  Message[]  $messages  Historial cronológico de la BD
     * @return Message[]
     */
    private function buildAlternatingHistory(array $messages): array
    {
        $result = [];
        $lastRole = null;

        foreach ($messages as $message) {
            $role = $message->role()->value();
            if ($role !== $lastRole) {
                $result[] = $message;
                $lastRole = $role;
            }
        }

        // Aseguramos que el historial termine en 'assistant' para que el LLM
        // reciba la nueva pregunta del usuario como el turno siguiente correcto.
        // Si el último mensaje es del usuario, lo quitamos (el handler
        // enviará la pregunta actual por separado como `question`).
        if (!empty($result) && $result[array_key_last($result)]->role()->value() === 'user') {
            array_pop($result);
        }

        return $result;
    }
}