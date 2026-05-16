<?php

declare(strict_types=1);

namespace App\Modules\AcademicDocuments\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadAcademicDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la autorización real la maneja el middleware auth
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'file'  => ['required', 'file', 'mimes:pdf', 'max:20480'], // máx 20 MB
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título del documento es obligatorio.',
            'title.min'      => 'El título debe tener al menos 3 caracteres.',
            'title.max'      => 'El título no puede superar los 255 caracteres.',
            'file.required'  => 'Debes seleccionar un archivo.',
            'file.mimes'     => 'Solo se permiten archivos PDF.',
            'file.max'       => 'El archivo no puede superar los 20 MB.',
        ];
    }
}
