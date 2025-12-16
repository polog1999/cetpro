<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreApoderadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validaciones basadas en create_apoderados_table.php
     * Todos los campos son required en la BD (no hay nullable)
     */
    public function rules(): array
    {
        return [
            'tipo_documento' => 'required|string|max:20',
            'nro_documento' => 'required|string|max:15|unique:apoderados,nro_documento',
            'nombres' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:50',
            'apellido_materno' => 'required|string|max:50',
            'telefono' => 'required|string|max:20',
            'email' => 'required|email|max:100|unique:apoderados,email',
        ];
    }
}
