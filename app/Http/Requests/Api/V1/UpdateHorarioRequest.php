<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => 'sometimes|required|string|max:50',
            'id_programa' => 'sometimes|required|exists:programas,id',
            'id_docente' => 'nullable|exists:docentes,id',
            'turno' => 'sometimes|required|string|in:Mañana,Tarde,Noche',
            'dias' => 'sometimes|required|string|max:100',
            'hora_inicio' => 'sometimes|required|date_format:H:i',
            'hora_fin' => 'sometimes|required|date_format:H:i',
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_fin' => 'sometimes|required|date',
            'vacantes' => 'sometimes|required|integer|min:1',
            'activo' => 'nullable|boolean',
        ];
    }
}
