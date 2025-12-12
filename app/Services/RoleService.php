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
        private RoleRepositoryInterface $roles,
        private \App\Repositories\PermisoRepositoryInterface $permisos
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
    
    /**
     * Crea un rol con sus permisos.
     *
     * @param array $roleData
     * @param array $permisosIds
     * @return Role
     * @throws ValidationException
     */
    public function crearConPermisos(array $roleData, array $permisosIds): Role
    {
        // Validar nombre único
        if ($this->roles->findByNombre($roleData['nombre'])) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe un rol con este nombre.',
            ]);
        }
        
        // Crear rol
        $role = $this->roles->create($roleData);
        
        // Asignar permisos solo si NO es admin
        if (!$role->es_admin && !empty($permisosIds)) {
            $this->validarYAsignarPermisos($role->id, $permisosIds);
        }
        
        return $role->fresh('permisos');
    }
    
    /**
     * Actualiza un rol y sus permisos.
     *
     * @param int $roleId
     * @param array $roleData
     * @param array $permisosIds
     * @return Role
     * @throws ValidationException
     */
    public function actualizarConPermisos(int $roleId, array $roleData, array $permisosIds): Role
    {
        $role = $this->roles->find($roleId);
        
        if (!$role) {
            throw ValidationException::withMessages([
                'role' => 'El rol no existe.',
            ]);
        }
        
        // Validar nombre único (ignorando el rol actual)
        $existente = $this->roles->findByNombre($roleData['nombre']);
        if ($existente && $existente->id !== $roleId) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe otro rol con este nombre.',
            ]);
        }
        
        // Actualizar rol
        $role = $this->roles->update($role, $roleData);
        
        // Sincronizar permisos
        if ($role->es_admin) {
            // Si es admin, quitar todos los permisos
            $role->permisos()->sync([]);
        } else {
            $this->validarYAsignarPermisos($role->id, $permisosIds);
        }
        
        return $role->fresh('permisos');
    }
    
    /**
     * Extrae IDs de permisos desde toggles del formulario.
     *
     * @param array $formData
     * @return array
     */
    public function extraerPermisosDeToggles(array $formData): array
    {
        $permisosIds = [];
        
        foreach ($formData as $key => $value) {
            if (str_starts_with($key, 'permiso_') && $value === true) {
                $permisoId = (int) str_replace('permiso_', '', $key);
                $permisosIds[] = $permisoId;
            }
        }
        
        return $permisosIds;
    }
    
    /**
     * Prepara datos de toggles para cargar en formulario.
     *
     * @param Role $role
     * @return array
     */
    public function prepararTogglesPermisos(Role $role): array
    {
        $data = [];
        
        if ($role->permisos) {
            foreach ($role->permisos as $permiso) {
                $data["permiso_{$permiso->id}"] = true;
            }
        }
        
        return $data;
    }
    
    /**
     * Valida y asigna permisos a un rol (método privado auxiliar).
     *
     * @param int $roleId
     * @param array $permisosIds
     * @return void
     * @throws ValidationException
     */
    private function validarYAsignarPermisos(int $roleId, array $permisosIds): void
    {
        if (empty($permisosIds)) {
            $this->roles->syncPermisos($this->roles->find($roleId), []);
            return;
        }
        
        // Validar que todos los permisos existen
        $permisosExistentes = $this->permisos->findByIds($permisosIds);
        
        if ($permisosExistentes->count() !== count($permisosIds)) {
            throw ValidationException::withMessages([
                'permisos' => 'Algunos permisos seleccionados no existen.',
            ]);
        }
        
        $role = $this->roles->find($roleId);
        $this->roles->syncPermisos($role, $permisosIds);
    }
}
