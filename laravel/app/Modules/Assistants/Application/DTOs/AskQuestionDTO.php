<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Application\DTOs;

/**
 * DTO que transporta los datos de una pregunta del estudiante.
 *
 * conversationUuid → null si es la primera pregunta (se crea conversación nueva)
 * userId           → null si el estudiante no está autenticado
 * sessionId        → se usa para usuarios anónimos
 */
final class AskQuestionDTO
{
    public function __construct(
        public readonly string  $question,
        public readonly ?string $conversationUuid,
        public readonly ?int    $userId,
        public readonly string  $sessionId,
    ) {}
}