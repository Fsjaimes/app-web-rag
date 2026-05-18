<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assistants\Application\Commands\AskQuestionCommand;
use App\Modules\Assistants\Application\DTOs\AskQuestionDTO;
use App\Modules\Assistants\Application\Handlers\AskQuestionHandler;
use App\Modules\Assistants\Application\Handlers\GetConversationHistoryHandler;
use App\Modules\Assistants\Infrastructure\Http\Requests\ChatRequest;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssistantController extends Controller
{
    public function __construct(
        private readonly AskQuestionHandler          $askQuestionHandler,
        private readonly GetConversationHistoryHandler $historyHandler,
    ) {}

    /**
     * Página principal del chat — renderiza el componente Vue.
     */
    public function index(): Response
    {
        return Inertia::render('Assistants/Chat', []);
    }

    /**
     * Recibe una pregunta del estudiante y devuelve la respuesta del asistente.
     * Accesible para usuarios autenticados y anónimos.
     *
     * POST /chat
     * Body: { question: string, conversation_uuid?: string }
     */
    public function chat(ChatRequest $request): JsonResponse
    {
        $dto = new AskQuestionDTO(
            question:         $request->input('question'),
            conversationUuid: $request->input('conversation_uuid'),
            userId:           auth()->id(),
            sessionId:        $request->session()->getId(),
        );

        $result = $this->askQuestionHandler->handle(new AskQuestionCommand($dto));

        return response()->json($result->toArray(), 201);
    }

    /**
     * Devuelve el historial completo de una conversación.
     * Permite al frontend cargar mensajes previos al recargar la página.
     *
     * GET /conversaciones/{uuid}/historial
     */
    public function history(string $uuid): JsonResponse
    {
        $conversationDTO = $this->historyHandler->handle($uuid);

        if ($conversationDTO === null) {
            return response()->json(['message' => 'Conversación no encontrada.'], 404);
        }

        return response()->json($conversationDTO->toArray());
    }
}
