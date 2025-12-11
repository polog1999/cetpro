<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Repositories\EstudianteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class EstudianteService
{
    public function __construct(
        private EstudianteRepositoryInterface $estudiantes
    ) {}

    public function obtenerTodos(): Collection
    {
        return $this->estudiantes->all();
    }

    public function buscar(int $id): ?Estudiante
    {
        return $this->estudiantes->find($id);
    }

    public function buscarConMatriculas(int $id): ?Estudiante
    {
        return $this->estudiantes->findWithMatriculas($id);
    }

    public function crear(array $data): Estudiante
    {
        if (isset($data['tipo_documento'], $data['nro_documento'])) {
            if ($this->estudiantes->findByDocumento($data['tipo_documento'], $data['nro_documento'])) {
                throw ValidationException::withMessages([
                    'nro_documento' => 'Ya existe un estudiante con este número de documento.',
                ]);
            }
        }

        return $this->estudiantes->create($data);
    }

    public function actualizar(int $id, array $data): Estudiante
    {
        $estudiante = $this->estudiantes->find($id);

        if (!$estudiante) {
            throw ValidationException::withMessages([
                'estudiante' => 'El estudiante no existe.',
            ]);
        }

        if (isset($data['tipo_documento'], $data['nro_documento'])) {
            $existente = $this->estudiantes->findByDocumento($data['tipo_documento'], $data['nro_documento']);
            if ($existente && $existente->id !== $id) {
                throw ValidationException::withMessages([
                    'nro_documento' => 'Ya existe otro estudiante con este número de documento.',
                ]);
            }
        }

        return $this->estudiantes->update($estudiante, $data);
    }

    public function eliminar(int $id): void
    {
        $estudiante = $this->estudiantes->findWithMatriculas($id);

        if (!$estudiante) {
            throw ValidationException::withMessages([
                'estudiante' => 'El estudiante no existe.',
            ]);
        }

        // Verificar si tiene matrículas activas
        $matriculasActivas = $estudiante->matriculas()
            ->where('estado', '!=', \App\Enums\EstadoMatricula::ANULADO)
            ->count();

        if ($matriculasActivas > 0) {
            throw ValidationException::withMessages([
                'estudiante' => 'No se puede eliminar el estudiante porque tiene matrículas activas.',
            ]);
        }

        $this->estudiantes->delete($estudiante);
    }

    public function validarEliminacion(int $id): array
    {
        $estudiante = $this->estudiantes->findWithMatriculas($id);

        if (!$estudiante) {
            return ['puede_eliminar' => false, 'mensaje' => 'El estudiante no existe.'];
        }

        $matriculasActivas = $estudiante->matriculas()
            ->where('estado', '!=', \App\Enums\EstadoMatricula::ANULADO)
            ->count();

        if ($matriculasActivas > 0) {
            return ['puede_eliminar' => false, 'mensaje' => 'El estudiante tiene matrículas activas.'];
        }

        return ['puede_eliminar' => true, 'mensaje' => 'El estudiante puede ser eliminado.'];
    }

    public function buscarPorNombre(string $search): Collection
    {
        return $this->estudiantes->searchByNombre($search);
    }
}
