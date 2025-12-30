<?php

namespace App\Policies;

use App\Models\Nota;
use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Usuario $user): bool
    {
        // Admin siempre puede
        if ($user->role?->es_admin) {
            return true;
        }

        // Profesor puede ver sus propias notas
        if ($user->esProfesor()) {
            return true;
        }

        // Verificar permiso general
        return $user->canAccessResource('notas');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Usuario $user, Nota $nota): bool
    {
        // Admin siempre puede
        if ($user->role?->es_admin) {
            return true;
        }

        // Profesor solo puede ver sus propias notas
        if ($user->esProfesor() && $user->docente_id) {
            return $nota->docente_id === $user->docente_id;
        }

        return $user->canAccessResource('notas');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Usuario $user): bool
    {
        // Admin siempre puede
        if ($user->role?->es_admin) {
            return true;
        }

        // Profesor puede crear notas
        if ($user->esProfesor()) {
            return true;
        }

        return $user->canAccessResource('notas');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Usuario $user, Nota $nota): bool
    {
        // Admin siempre puede
        if ($user->role?->es_admin) {
            return true;
        }

        // Profesor solo puede editar sus propias notas
        if ($user->esProfesor() && $user->docente_id) {
            return $nota->docente_id === $user->docente_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Usuario $user, Nota $nota): bool
    {
        // Solo admin puede eliminar
        if ($user->role?->es_admin) {
            return true;
        }

        // Profesores NO pueden eliminar notas
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Usuario $user, Nota $nota): bool
    {
        // Solo admin
        return $user->role?->es_admin ?? false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Usuario $user, Nota $nota): bool
    {
        // Solo admin
        return $user->role?->es_admin ?? false;
    }
}
