<?php

namespace App\Repositories\Eloquent;

use App\Models\Docente;
use App\Repositories\DocenteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Implementación Eloquent del repositorio de Docentes.
 */
class DocenteRepository implements DocenteRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function all(): Collection
    {
        return Docente::all();
    }

    /**
     * @inheritDoc
     */
    public function find(int $id): ?Docente
    {
        return Docente::find($id);
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): Docente
    {
        return Docente::create($data);
    }

    /**
     * @inheritDoc
     */
    public function update(Docente $docente, array $data): Docente
    {
        $docente->update($data);
        return $docente->fresh();
    }

    /**
     * @inheritDoc
     */
    public function delete(Docente $docente): void
    {
        $docente->delete();
    }

    /**
     * @inheritDoc
     */
    public function findByDocumento(string $tipoDocumento, string $nroDocumento): ?Docente
    {
        return Docente::where('tipo_documento', $tipoDocumento)
            ->where('nro_documento', $nroDocumento)
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function hasDependencies(int $id): bool
    {
        $docente = Docente::withCount('horarios')->find($id);
        
        return $docente && $docente->horarios_count > 0;
    }
}
