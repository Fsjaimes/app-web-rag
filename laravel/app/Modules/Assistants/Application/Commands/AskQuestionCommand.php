<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Application\Commands;

use App\Modules\Assistants\Application\DTOs\AskQuestionDTO;

/**
 * Command que representa la intención de hacer una pregunta al asistente.
 */
final class AskQuestionCommand
{
    public function __construct(
        public readonly AskQuestionDTO $dto
    ) {}
}