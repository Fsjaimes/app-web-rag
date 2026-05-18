<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Infrastructure\Services;

use App\Modules\Assistants\Domain\Entities\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

/**
 * Implementación concreta que envía preguntas al pipeline RAG de FastAPI.
 *
 * FastAPI recibe la pregunta + historial, consulta ChromaDB,
 * construye el prompt con los fragmentos relevantes y llama al LLM.
 */
class FastAPIAIAssistantService implements AIAssistantServiceInterface
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ai_service.url'), '/');
        $this->timeout = config('services.ai_service.timeout', 30);
    }

    /**
     * @param Message[] $history
     * @return array{answer: string, sources: array|null}
     */
    public function ask(
        string $question,
        string $conversationId,
        array  $history,
    ): array {
        $response = Http::timeout($this->timeout)
            ->post("{$this->baseUrl}/ask", [
                'question'        => $question,
                'conversation_id' => $conversationId,
                'history'         => $this->serializeHistory($history),
            ]);

        $response->throw();

        $body = $response->json();

        return [
            'answer'  => $body['answer'],
            'sources' => $body['sources'] ?? null,
        ];
    }

    /**
     * Convierte las entidades Message al formato que espera FastAPI:
     * [{"role": "user"|"assistant", "content": "..."}]
     *
     * @param Message[] $messages
     */
    private function serializeHistory(array $messages): array
    {
        return array_map(
            fn(Message $m) => [
                'role'    => $m->role()->value(),
                'content' => $m->content(),
            ],
            $messages,
        );
    }
}
