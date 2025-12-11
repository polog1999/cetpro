<?php

namespace App\Repositories;

use App\Models\Permiso;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface para el repositorio de Permisos.
 * 
 * Encapsula operaciones de acceso a datos para el modelo Permiso.
 */
interface PermisoRepositoryInterface
{
    /**
     * Obtiene todos los permisos.
     *
     * @return Collection<Permiso>
     */
    public function all(): Collection;

    /**
     * Busca un permiso por su ID.
     *
     * @param int $id
     * @return Permiso|null
     */
    public function find(int $id): ?Permiso;

    /**
     * Busca un permiso por su recurso.
     *
     * @param string $recurso
     * @return Permiso|null
     */
    public function findByRecurso(string $recurso): ?Permiso;
}
