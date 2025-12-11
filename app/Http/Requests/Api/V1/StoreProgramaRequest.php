<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:200',
            'id_especialidad' => 'required|exists:especialidades,id',
            'tipo' => 'required|string|in:Programa,Formacion Continua',
            'duracion' => 'required|integer|min:1',
            'descripcion' => 'nullable|string',
        ];
    }
}
