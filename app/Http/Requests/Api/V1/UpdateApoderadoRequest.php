<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApoderadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $apoderadoId = $this->route('apoderado');
        
        return [
            'tipo_documento' => 'sometimes|required|string|max:20',
            'nro_documento' => 'sometimes|required|string|max:15|unique:apoderados,nro_documento,' . $apoderadoId,
            'nombres' => 'sometimes|required|string|max:100',
            'apellido_paterno' => 'sometimes|required|string|max:50',
            'apellido_materno' => 'sometimes|required|string|max:50',
            'telefono' => 'sometimes|required|string|max:20',
            'email' => 'sometimes|required|email|max:100|unique:apoderados,email,' . $apoderadoId,
        ];
    }
}
