<?php

namespace App\Services;

use App\Models\Role;
use App\Repositories\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de lógica de negocio para Roles.
 * 
 * Responsabilidad: Gestión de roles y sus permisos asociados,
 * aplicando reglas de negocio y validaciones.
 */
class RoleService
{
    /**
     * Constructor con inyección de dependencias.
     */
    public function __construct(
        private RoleRepositoryInterface $roles
    ) {}

    /**
     * Obtiene todos los roles.
     *
     * @return Collection<Role>
     */
    public function obtenerTodos(): Collection
    {
        return $this->roles->all();
    }

    /**
     * Busca un role por ID.
     *
     * @param int $id
     * @return Role|null
     */
    public function buscar(int $id): ?Role
    {
        return $this->roles->find($id);
    }

    /**
     * Busca un role con sus permisos cargados.
     *
     * @param int $id
     * @return Role|null
     */
    public function buscarConPermisos(int $id): ?Role
    {
        return $this->roles->findWithPermisos($id);
    }

    /**
     * Crea un nuevo role.
     *
     * @param array $data
     * @return Role
     */
    public function crear(array $data): Role
    {
        return $this->roles->create($data);
    }

    /**
     * Actualiza un role existente.
     *
     * @param int $id
     * @param array $data
     * @return Role
     * @throws ValidationException
     */
    public function actualizar(int $id, array $data): Role
    {
        $role = $this->roles->find($id);

        if (!$role) {
            throw ValidationException::withMessages([
                'role' => 'El rol no existe.',
            ]);
        }

        return $this->roles->update($role, $data);
    }

    /**
     * Elimina un role después de validar que no tenga usuarios asignados.
     *
     * @param int $id
     * @return void
     * @throws ValidationException
     */
    public function eliminar(int $id): void
    {
        $role = $this->roles->find($id);

        if (!$role) {
            throw ValidationException::withMessages([
                'role' => 'El rol no existe.',
            ]);
        }

        // Validar que no tenga usuarios asignados
        if ($this->roles->hasDependencies($id)) {
            throw ValidationException::withMessages([
                'role' => 'No se puede eliminar el rol porque tiene usuarios asignados.',
            ]);
        }

        $this->roles->delete($role);
    }

    /**
     * Asigna permisos a un role.
     *
     * @param int $roleId
     * @param array $permisoIds
     * @return void
     * @throws ValidationException
     */
    public function asignarPermisos(int $roleId, array $permisoIds): void
    {
        $role = $this->roles->find($roleId);

        if (!$role) {
            throw ValidationException::withMessages([
                'role' => 'El rol no existe.',
            ]);
        }

        $this->roles->syncPermisos($role, $permisoIds);
    }

    /**
     * Valida si un role puede ser eliminado.
     *
     * @param int $id
     * @return array{puede_eliminar: bool, mensaje: string}
     */
    public function validarEliminacion(int $id): array
    {
        $role = $this->roles->find($id);

        if (!$role) {
            return [
                'puede_eliminar' => false,
                'mensaje' => 'El rol no existe.',
            ];
        }

        if ($this->roles->hasDependencies($id)) {
            return [
                'puede_eliminar' => false,
                'mensaje' => 'El rol tiene usuarios asignados y no puede ser eliminado.',
            ];
        }

        return [
            'puede_eliminar' => true,
            'mensaje' => 'El rol puede ser eliminado.',
        ];
    }
}
