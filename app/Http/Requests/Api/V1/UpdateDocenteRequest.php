<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_documento' => 'sometimes|required|string',
            'nro_documento' => 'sometimes|required|string',
            'nombres' => 'sometimes|required|string',
            'apellido_paterno' => 'sometimes|required|string',
            'apellido_materno' => 'sometimes|required|string',
            'especialidad' => 'sometimes|required|string',
        ];
    }
}
