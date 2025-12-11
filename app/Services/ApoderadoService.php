<?php

namespace App\Services;

use App\Models\Apoderado;
use App\Repositories\ApoderadoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ApoderadoService
{
    public function __construct(
        private ApoderadoRepositoryInterface $apoderados
    ) {}

    public function obtenerTodos(): Collection
    {
        return $this->apoderados->all();
    }

    public function buscar(int $id): ?Apoderado
    {
        return $this->apoderados->find($id);
    }

    public function crear(array $data): Apoderado
    {
        return $this->apoderados->create($data);
    }

    public function actualizar(int $id, array $data): Apoderado
    {
        $apoderado = $this->apoderados->find($id);

        if (!$apoderado) {
            throw ValidationException::withMessages([
                'apoderado' => 'El apoderado no existe.',
            ]);
        }

        return $this->apoderados->update($apoderado, $data);
    }

    public function eliminar(int $id): void
    {
        $apoderado = $this->apoderados->findWithEstudiantes($id);

        if (!$apoderado) {
            throw ValidationException::withMessages([
                'apoderado' => 'El apoderado no existe.',
            ]);
        }

        // Verificar si tiene estudiantes asociados
        if ($apoderado->relacionExists('estudiantes')) {
            throw ValidationException::withMessages([
                'apoderado' => 'No se puede eliminar el apoderado porque tiene estudiantes asociados.',
            ]);
        }

        $this->apoderados->delete($apoderado);
    }
}
