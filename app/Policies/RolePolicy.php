<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\Role;

class RolePolicy
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
    public function view(Usuario $user, Role $model): bool
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
    public function update(Usuario $user, Role $model): bool
    {
        return $user->role && $user->role->es_admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Usuario $user, Role $model): bool
    {
        return $user->role && $user->role->es_admin;
    }
}
