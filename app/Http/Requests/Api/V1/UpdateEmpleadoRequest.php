<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres' => 'sometimes|required|string|max:100',
            'apellido_paterno' => 'sometimes|required|string|max:50',
            'apellido_materno' => 'sometimes|required|string|max:50',
            'cargo' => 'sometimes|required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
        ];
    }
}
