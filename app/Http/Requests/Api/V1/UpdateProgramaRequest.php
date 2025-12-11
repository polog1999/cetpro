<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'sometimes|required|string|max:200',
            'id_especialidad' => 'sometimes|required|exists:especialidades,id',
            'tipo' => 'sometimes|required|string|in:Programa,Formacion Continua',
            'duracion' => 'sometimes|required|integer|min:1',
            'descripcion' => 'nullable|string',
        ];
    }
}
