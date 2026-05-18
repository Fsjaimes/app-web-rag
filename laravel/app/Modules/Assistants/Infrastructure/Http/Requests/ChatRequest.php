<?php

declare(strict_types=1);

namespace App\Modules\Assistants\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // accesible para usuarios autenticados y anónimos
    }

    public function rules(): array
    {
        return [
            'question'         => ['required', 'string', 'min:2', 'max:2000'],
            'conversation_uuid' => ['nullable', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => 'La pregunta es obligatoria.',
            'question.min'      => 'La pregunta debe tener al menos 2 caracteres.',
            'question.max'      => 'La pregunta no puede superar los 2000 caracteres.',
            'conversation_uuid.uuid' => 'El identificador de conversación no es válido.',
        ];
    }
}
