<?php

namespace App\Services;

use App\Models\Docente;
use App\Repositories\DocenteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de lógica de negocio para Docentes.
 */
class DocenteService
{
    public function __construct(
        private DocenteRepositoryInterface $docentes
    ) {}

    public function obtenerTodos(): Collection
    {
        return $this->docentes->all();
    }

    public function buscar(int $id): ?Docente
    {
        return $this->docentes->find($id);
    }

    public function crear(array $data): Docente
    {
        // Validar documento único
        if (isset($data['tipo_documento'], $data['nro_documento'])) {
            if ($this->docentes->findByDocumento($data['tipo_documento'], $data['nro_documento'])) {
                throw ValidationException::withMessages([
                    'nro_documento' => 'Ya existe un docente con este número de documento.',
                ]);
            }
        }

        return $this->docentes->create($data);
    }

    public function actualizar(int $id, array $data): Docente
    {
        $docente = $this->docentes->find($id);

        if (!$docente) {
            throw ValidationException::withMessages([
                'docente' => 'El docente no existe.',
            ]);
        }

        // Validar documento único si cambió
        if (isset($data['tipo_documento'], $data['nro_documento'])) {
            $existente = $this->docentes->findByDocumento($data['tipo_documento'], $data['nro_documento']);
            if ($existente && $existente->id !== $id) {
                throw ValidationException::withMessages([
                    'nro_documento' => 'Ya existe otro docente con este número de documento.',
                ]);
            }
        }

        return $this->docentes->update($docente, $data);
    }

    public function eliminar(int $id): void
    {
        $docente = $this->docentes->find($id);

        if (!$docente) {
            throw ValidationException::withMessages([
                'docente' => 'El docente no existe.',
            ]);
        }

        if ($this->docentes->hasDependencies($id)) {
            throw ValidationException::withMessages([
                'docente' => 'No se puede eliminar el docente porque tiene horarios asignados.',
            ]);
        }

        $this->docentes->delete($docente);
    }

    public function validarEliminacion(int $id): array
    {
        $doc = $this->docentes->find($id);

        if (!$docente) {
            return ['puede_eliminar' => false, 'mensaje' => 'El docente no existe.'];
        }

        if ($this->docentes->hasDependencies($id)) {
            return ['puede_eliminar' => false, 'mensaje' => 'El docente tiene horarios asignados.'];
        }

        return ['puede_eliminar' => true, 'mensaje' => 'El docente puede ser eliminado.'];
    }
}
