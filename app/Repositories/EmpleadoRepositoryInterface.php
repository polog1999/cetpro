<?php

namespace App\Repositories;

use App\Models\Empleado;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface para el repositorio de Empleados.
 * 
 * Encapsula operaciones de acceso a datos para el modelo Empleado.
 */
interface EmpleadoRepositoryInterface
{
    /**
     * Obtiene todos los empleados.
     *
     * @return Collection<Empleado>
     */
    public function all(): Collection;

    /**
     * Busca un empleado por su ID.
     *
     * @param int $id
     * @return Empleado|null
     */
    public function find(int $id): ?Empleado;

    /**
     * Crea un nuevo empleado.
     *
     * @param array $data
     * @return Empleado
     */
    public function create(array $data): Empleado;

    /**
     * Actualiza un empleado existente.
     *
     * @param Empleado $empleado
     * @param array $data
     * @return Empleado
     */
    public function update(Empleado $empleado, array $data): Empleado;

    /**
     * Elimina un empleado.
     *
     * @param Empleado $empleado
     * @return void
     */
    public function delete(Empleado $empleado): void;

    /**
     * Verifica si el empleado tiene dependencias (usuarios asociados).
     *
     * @param int $id
     * @return bool
     */
    public function hasDependencies(int $id): bool;
}
