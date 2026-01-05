<?php

namespace App\Repositories\Eloquent;

use App\Models\Estudiante;
use App\Repositories\EstudianteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EstudianteRepository implements EstudianteRepositoryInterface
{
    public function all(): Collection
    {
        return Estudiante::all();
    }

    public function find(int $id): ?Estudiante
    {
        return Estudiante::find($id);
    }

    public function create(array $data): Estudiante
    {
        return Estudiante::create($data);
    }

    public function update(Estudiante $estudiante, array $data): Estudiante
    {
        $estudiante->update($data);
        return $estudiante->fresh();
    }

    public function delete(Estudiante $estudiante): void
    {
        $estudiante->delete();
    }

    public function findByDocumento(string|\App\Enums\TipoDocumento $tipoDocumento, string $nroDocumento): ?Estudiante
    {
        // Convertir enum a string si es necesario
        $tipoDocumentoValue = $tipoDocumento instanceof \App\Enums\TipoDocumento 
            ? $tipoDocumento->value 
            : $tipoDocumento;
            
        return Estudiante::where('tipo_documento', $tipoDocumentoValue)
            ->where('nro_documento', $nroDocumento)
            ->first();
    }

    public function findWithMatriculas(int $id): ?Estudiante
    {
        return Estudiante::with('matriculas')->find($id);
    }

    public function findWithApoderado(int $id): ?Estudiante
    {
        return Estudiante::with('apoderado')->find($id);
    }

    public function searchByNombre(string $search): Collection
    {
        return Estudiante::where('nombres', 'ilike', "%{$search}%")
            ->orWhere('apellido_paterno', 'ilike', "%{$search}%")
            ->orWhere('apellido_materno', 'ilike', "%{$search}%")
            ->get();
    }
}
