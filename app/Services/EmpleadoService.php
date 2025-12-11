<?php

namespace App\Services;

use App\Models\Empleado;
use App\Repositories\EmpleadoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de lógica de negocio para Empleados.
 */
class EmpleadoService
{
    public function __construct(
        private EmpleadoRepositoryInterface $empleados
    ) {}

    public function obtenerTodos(): Collection
    {
        return $this->empleados->all();
    }

    public function buscar(int $id): ?Empleado
    {
        return $this->empleados->find($id);
    }

    public function crear(array $data): Empleado
    {
        return $this->empleados->create($data);
    }

    public function actualizar(int $id, array $data): Empleado
    {
        $empleado = $this->empleados->find($id);

        if (!$empleado) {
            throw ValidationException::withMessages([
                'empleado' => 'El empleado no existe.',
            ]);
        }

        return $this->empleados->update($empleado, $data);
    }

    public function eliminar(int $id): void
    {
        $empleado = $this->empleados->find($id);

        if (!$empleado) {
            throw ValidationException::withMessages([
                'empleado' => 'El empleado no existe.',
            ]);
        }

        if ($this->empleados->hasDependencies($id)) {
            throw ValidationException::withMessages([
                'empleado' => 'No se puede eliminar el empleado porque tiene usuarios asociados.',
            ]);
        }

        $this->empleados->delete($empleado);
    }

    public function validarEliminacion(int $id): array
    {
        $empleado = $this->empleados->find($id);

        if (!$empleado) {
            return ['puede_eliminar' => false, 'mensaje' => 'El empleado no existe.'];
        }

        if ($this->empleados->hasDependencies($id)) {
            return ['puede_eliminar' => false, 'mensaje' => 'El empleado tiene usuarios asociados.'];
        }

        return ['puede_eliminar' => true, 'mensaje' => 'El empleado puede ser eliminado.'];
    }
}
