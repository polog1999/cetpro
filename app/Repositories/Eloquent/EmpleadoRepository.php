<?php

namespace App\Repositories\Eloquent;

use App\Models\Empleado;
use App\Repositories\EmpleadoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Implementación Eloquent del repositorio de Empleados.
 */
class EmpleadoRepository implements EmpleadoRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function all(): Collection
    {
        return Empleado::all();
    }

    /**
     * @inheritDoc
     */
    public function find(int $id): ?Empleado
    {
        return Empleado::find($id);
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): Empleado
    {
        return Empleado::create($data);
    }

    /**
     * @inheritDoc
     */
    public function update(Empleado $empleado, array $data): Empleado
    {
        $empleado->update($data);
        return $empleado->fresh();
    }

    /**
     * @inheritDoc
     */
    public function delete(Empleado $empleado): void
    {
        $empleado->delete();
    }

    /**
     * @inheritDoc
     */
    public function hasDependencies(int $id): bool
    {
        $empleado = Empleado::find($id);
        
        if (!$empleado) {
            return false;
        }

        // Verificar si tiene usuarios asociados
        return \App\Models\Usuario::where('empleado_id', $id)->exists();
    }
}
