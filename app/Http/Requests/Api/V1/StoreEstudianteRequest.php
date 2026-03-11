<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación basadas en el esquema real de la BD.
     * 
     * Tabla: estudiantes
     * - genero, estado_civil, fecha_nacimiento, telefono, email son NULL ABLE
     * - direccion es nullable
     * - grado_instruccion, provincia, distrito son nullable
     */
    public function rules(): array
    {
        return [
            // Campos requeridos
            'tipo_documento' => 'required|string|max:20',
            'nro_documento' => 'required|string|max:15|unique:estudiantes,nro_documento',
            'nombres' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:50',
            'apellido_materno' => 'required|string|max:50',
            
            // Campos nullable según BD
            'genero' => 'nullable|string|max:20',
            'estado_civil' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:200',
            
            // Campos adicionales
            'grado_instruccion' => 'nullable|string',
            'provincia' => 'nullable|string',
            'distrito' => 'nullable|string',
        ];
    }
}
