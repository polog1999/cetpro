<?php

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface para el repositorio de Roles.
 * 
 * Encapsula operaciones de acceso a datos para el modelo Role,
 * incluyendo gestión de permisos asociados.
 */
interface RoleRepositoryInterface
{
    /**
     * Obtiene todos los roles.
     *
     * @return Collection<Role>
     */
    public function all(): Collection;

    /**
     * Busca un role por su ID.
     *
     * @param int $id
     * @return Role|null
     */
    public function find(int $id): ?Role;

    /**
     * Crea un nuevo role.
     *
     * @param array $data
     * @return Role
     */
    public function create(array $data): Role;

    /**
     * Actualiza un role existente.
     *
     * @param Role $role
     * @param array $data
     * @return Role
     */
    public function update(Role $role, array $data): Role;

    /**
     * Elimina un role.
     *
     * @param Role $role
     * @return void
     */
    public function delete(Role $role): void;

    /**
     * Busca un role con sus permisos cargados.
     *
     * @param int $id
     * @return Role|null
     */
    public function findWithPermisos(int $id): ?Role;

    /**
     * Sincroniza los permisos de un role.
     *
     * @param Role $role
     * @param array $permisoIds
     * @return void
     */
    public function syncPermisos(Role $role, array $permisoIds): void;

    /**
     * Verifica si el role tiene dependencias (usuarios asignados).
     *
     * @param int $id
     * @return bool
     */
    public function hasDependencies(int $id): bool;
}
