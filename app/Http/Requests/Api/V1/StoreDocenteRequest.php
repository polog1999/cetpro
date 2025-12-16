<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validaciones basadas en create_docentes_table.php
     * La tabla solo tiene: tipo_documento, nro_documento, nombres, apellido_paterno, apellido_materno, especialidad
     * NO tiene campos telefono ni email
     */
    public function rules(): array
    {
        return [
            'tipo_documento' => 'required|string',
            'nro_documento' => 'required|string',
            'nombres' => 'required|string',
            'apellido_paterno' => 'required|string',
            'apellido_materno' => 'required|string',
            'especialidad' => 'required|string',
        ];
    }
}
