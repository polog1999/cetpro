<?php

namespace App\Repositories\Eloquent;

use App\Models\Permiso;
use App\Repositories\PermisoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Implementación Eloquent del repositorio de Permisos.
 */
class PermisoRepository implements PermisoRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function all(): Collection
    {
        return Permiso::all();
    }

    /**
     * @inheritDoc
     */
    public function find(int $id): ?Permiso
    {
        return Permiso::find($id);
    }

    /**
     * @inheritDoc
     */
    public function findByRecurso(string $recurso): ?Permiso
    {
        return Permiso::where('recurso', $recurso)->first();
    }
}
