<?php

namespace App\Observers;

use App\Models\Usuario;
use App\Models\Docente;
use Illuminate\Support\Facades\Log;
use Exception;

class UsuarioObserver
{
    /**
     * Handle the Usuario "created" event.
     * Cuando se crea un usuario de tipo profesor, se crea automáticamente el registro de Docente
     */
    public function created(Usuario $usuario): void
    {
        // Recargar relaciones para obtener datos actualizados
        $usuario->load(['empleado', 'role']);

        // Verificar si el usuario es profesor y no tiene docente asociado
        if ($usuario->esProfesor() && !$usuario->docente_id) {
            try {
                $this->crearDocenteDesdeUsuario($usuario);
                Log::info("Docente creado automáticamente para usuario: {$usuario->usuario}");
            } catch (Exception $e) {
                Log::error("Error al crear docente para usuario {$usuario->usuario}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Handle the Usuario "updated" event.
     * Sincroniza los datos del docente cuando se actualiza el usuario
     */
    public function updated(Usuario $usuario): void
    {
        // Recargar relaciones para obtener datos actualizados
        $usuario->load(['empleado', 'role', 'docente']);

        // Si es profesor y tiene docente asociado, actualizar sus datos
        if ($usuario->esProfesor() && $usuario->docente_id && $usuario->empleado) {
            try {
                $this->actualizarDocenteDesdeUsuario($usuario);
                Log::info("Docente actualizado para usuario: {$usuario->usuario}");
            } catch (Exception $e) {
                Log::error("Error al actualizar docente para usuario {$usuario->usuario}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Crea un registro de Docente a partir de los datos del Empleado del Usuario
     * 
     * @param Usuario $usuario
     * @throws Exception
     */
    protected function crearDocenteDesdeUsuario(Usuario $usuario): void
    {
        // Obtener el empleado del usuario
        $empleado = $usuario->empleado;

        if (!$empleado) {
            throw new Exception("No se encontró empleado asociado al usuario {$usuario->usuario}");
        }

        // Validar que el empleado tenga los datos requeridos
        if (!$empleado->nombre || !$empleado->apellido_paterno) {
            throw new Exception("El empleado no tiene nombre o apellido paterno definido");
        }

        // Crear el docente con los datos del empleado
        $docente = Docente::create([
            'tipo_documento' => $empleado->tipo_documento,
            'nro_documento' => $empleado->num_documento,
            'nombres' => $empleado->nombre,
            'apellido_paterno' => $empleado->apellido_paterno,
            'apellido_materno' => $empleado->apellido_materno ?? '',
        ]);

        // Asignar el docente al usuario (sin disparar eventos para evitar bucles infinitos)
        $usuario->update(['docente_id' => $docente->id]);
    }

    /**
     * Actualiza los datos del Docente basándose en los cambios del Empleado
     * 
     * @param Usuario $usuario
     * @throws Exception
     */
    protected function actualizarDocenteDesdeUsuario(Usuario $usuario): void
    {
        $docente = $usuario->docente;
        $empleado = $usuario->empleado;

        if (!$docente || !$empleado) {
            throw new Exception("No se encontró docente o empleado para sincronizar");
        }

        // Actualizar los datos del docente con los datos del empleado
        $docente->update([
            'tipo_documento' => $empleado->tipo_documento,
            'nro_documento' => $empleado->num_documento,
            'nombres' => $empleado->nombre,
            'apellido_paterno' => $empleado->apellido_paterno,
            'apellido_materno' => $empleado->apellido_materno ?? '',
        ]);
    }
}
