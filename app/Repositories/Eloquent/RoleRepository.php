<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Implementación Eloquent del repositorio de Roles.
 */
class RoleRepository implements RoleRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function all(): Collection
    {
        return Role::all();
    }

    /**
     * @inheritDoc
     */
    public function find(int $id): ?Role
    {
        return Role::find($id);
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): Role
    {
        return Role::create($data);
    }

    /**
     * @inheritDoc
     */
    public function update(Role $role, array $data): Role
    {
        $role->update($data);
        return $role->fresh();
    }

    /**
     * @inheritDoc
     */
    public function delete(Role $role): void
    {
        $role->delete();
    }

    /**
     * @inheritDoc
     */
    public function findWithPermisos(int $id): ?Role
    {
        return Role::with('permisos')->find($id);
    }

    /**
     * @inheritDoc
     */
    public function syncPermisos(Role $role, array $permisoIds): void
    {
        $role->permisos()->sync($permisoIds);
    }

    /**
     * @inheritDoc
     */
    public function hasDependencies(int $id): bool
    {
        $role = Role::withCount('usuarios')->find($id);
        
        return $role && $role->usuarios_count > 0;
    }
}
