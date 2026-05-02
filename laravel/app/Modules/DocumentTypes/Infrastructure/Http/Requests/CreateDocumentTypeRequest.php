<?php

declare(strict_types=1);

namespace App\Modules\DocumentTypes\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeDateFormat;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeInventoryMovementType;
use App\Modules\DocumentTypes\Domain\ValueObjects\DocumentTypeLengthSequence;

class CreateDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    
    public function rules(): array
    {
        return [
            'prefix' => 'required|string|max:4',
            'name' => 'required|string',
            'affectsInventory' => 'required|boolean',
            'inventoryMovementType' => ['nullable', 'integer', Rule::in(DocumentTypeInventoryMovementType::ids())],
            'allowNegativeInventory' => 'required|boolean',
            'hasPrefix' => 'required|boolean',
            'hasDate' => 'required|boolean',
            'dateFormat' => ['nullable', 'string', Rule::in(DocumentTypeDateFormat::codes())],
            'lengthSequence' => ['required', 'integer', Rule::in(DocumentTypeLengthSequence::ids())],
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'prefix.required' => 'El campo prefijo es requerido',
            'prefix.max' => 'El campo prefijo debe tener máximo 4 caracteres',
            'name.required' => 'El campo nombre es requerido',
            'affectsInventory.boolean' => 'El campo afecta inventario debe ser un booleano',
            'inventoryMovementType.integer' => 'El campo tipo movimiento inventario debe ser un número entero',
            'allowNegativeInventory.boolean' => 'El campo permite tipo movimiento inventario debe ser un booleano',
            'hasPrefix.boolean' => 'El campo tiene prefijo debe ser un booleano',
            'hasDate.boolean' => 'El campo tiene fecha debe ser un booleano',
            'dateFormat.string' => 'El campo formato fecha debe ser un string',
            'lengthSequence.required' => 'El campo longitud de secuencia es requerido',
            'lengthSequence.integer' => 'El campo longitud de secuencia debe ser un número entero',
            'lengthSequence.min' => 'El campo longitud de secuencia debe ser mayor a 0',
            'status.required' => 'El campo estado es requerido',
            'status.boolean' => 'El campo estado debe ser un booleano',
        ];
    }
}