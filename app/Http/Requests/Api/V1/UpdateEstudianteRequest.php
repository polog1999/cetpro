<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $estudianteId = $this->route('estudiante');
        
        return [
            'tipo_documento' => 'sometimes|required|string|max:20',
            'nro_documento' => 'sometimes|required|string|max:15|unique:estudiantes,nro_documento,' . $estudianteId,
            'nombres' => 'sometimes|required|string|max:100',
            'apellido_paterno' => 'sometimes|required|string|max:50',
            'apellido_materno' => 'sometimes|required|string|max:50',
            'genero' => 'nullable|string|max:20',
            'estado_civil' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:200',
            'grado_instruccion' => 'nullable|string',
            'provincia' => 'nullable|string',
            'distrito' => 'nullable|string',
        ];
    }
}
