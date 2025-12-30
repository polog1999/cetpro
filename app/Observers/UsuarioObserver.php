<?php

namespace App\Observers;

use App\Models\Usuario;
use App\Models\Docente;

class UsuarioObserver
{
    /**
     * Handle the Usuario "created" event.
     * Cuando se crea un usuario de tipo profesor, se crea automáticamente el registro de Docente
     */
    public function created(Usuario $usuario): void
    {
        // Verificar si el usuario es profesor
        if ($usuario->esProfesor() && !$usuario->docente_id) {
            $this->crearDocenteDesdeUsuario($usuario);
        }
    }

    /**
     * Handle the Usuario "updated" event.
     * Sincroniza los datos del docente cuando se actualiza el usuario
     */
    public function updated(Usuario $usuario): void
    {
        // Si es profesor y tiene docente asociado, actualizar sus datos
        if ($usuario->esProfesor() && $usuario->docente_id && $usuario->empleado) {
            $this->actualizarDocenteDesdeUsuario($usuario);
        }
    }

    /**
     * Crea un registro de Docente a partir de los datos del Empleado del Usuario
     */
    protected function crearDocenteDesdeUsuario(Usuario $usuario): void
    {
        // Obtener el empleado del usuario
        $empleado = $usuario->empleado;

        if (!$empleado) {
            return; // No se puede crear docente sin empleado
        }

        // Crear el docente con los datos del empleado
        $docente = Docente::create([
            'tipo_documento' => $empleado->tipo_documento,
            'nro_documento' => $empleado->num_documento,
            'nombres' => $empleado->nombre,
            'apellido_paterno' => $empleado->apellido_paterno,
            'apellido_materno' => $empleado->apellido_materno ?? '',
        ]);

        // Asignar el docente al usuario
        $usuario->update(['docente_id' => $docente->id]);
    }

    /**
     * Actualiza los datos del Docente basándose en los cambios del Empleado
     */
    protected function actualizarDocenteDesdeUsuario(Usuario $usuario): void
    {
        $docente = $usuario->docente;
        $empleado = $usuario->empleado;

        if (!$docente || !$empleado) {
            return;
        }

        // Actualizar solo si hay cambios en el empleado
        $docente->update([
            'tipo_documento' => $empleado->tipo_documento,
            'nro_documento' => $empleado->num_documento,
            'nombres' => $empleado->nombre,
            'apellido_paterno' => $empleado->apellido_paterno,
            'apellido_materno' => $empleado->apellido_materno ?? '',
        ]);
    }
}
