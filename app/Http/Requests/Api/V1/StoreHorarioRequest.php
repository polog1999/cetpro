<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreHorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => 'required|string|max:50',
            'id_programa' => 'required|exists:programas,id',
            'id_docente' => 'nullable|exists:docentes,id',
            'turno' => 'required|string|in:Mañana,Tarde,Noche',
            'dias' => 'required|string|max:100',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'vacantes' => 'required|integer|min:1',
            'activo' => 'nullable|boolean',
        ];
    }
}
