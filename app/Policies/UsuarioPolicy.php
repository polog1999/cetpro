<?php

namespace App\Policies;

use App\Models\Usuario;

class UsuarioPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Usuario $user): bool
    {
        return $user->role && $user->role->es_admin;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Usuario $user, Usuario $model): bool
    {
        return $user->role && $user->role->es_admin;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Usuario $user): bool
    {
        return $user->role && $user->role->es_admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Usuario $user, Usuario $model): bool
    {
        return $user->role && $user->role->es_admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Usuario $user, Usuario $model): bool
    {
        // Solo admin puede borrar, y no puede borrarse a sí mismo
        return $user->role && $user->role->es_admin && $user->id !== $model->id;
    }
}
