<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreMatriculaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estudiante_id' => 'required|exists:estudiantes,id',
            'tipo_matricula' => 'required|string|in:Programa,Formación continua,Curso,Módulo,Unidad',
            'horario_id' => 'nullable|exists:horarios,id_horario',
            'id_curso' => 'nullable|exists:cursos,id_curso',
            'id_unidad' => 'nullable|exists:unidads,id_unidad',
            'estado' => 'nullable|string|in:Activo,Finalizado,Anulado',
            'fecha_matricula' => 'nullable|date',
        ];
    }
}
