<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Infrastructure\Services;

use App\Modules\Assistants\Domain\Entities\Message;

/**
 * Contrato para el servicio que envía preguntas a FastAPI
 * y recibe respuestas del pipeline RAG.
 */
interface AIAssistantServiceInterface
{
    /**
     * Envía una pregunta a FastAPI con el historial de conversación.
     *
     * @param Message[] $history mensajes previos de la conversación
     * @return array{answer: string, sources: array|null}
     */
    public function ask(
        string $question,
        string $conversationId,
        array  $history,
    ): array;
}