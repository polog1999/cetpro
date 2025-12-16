<?php

namespace App\Repositories\Eloquent;

use App\Models\Especialidad;
use App\Repositories\EspecialidadRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EspecialidadRepository implements EspecialidadRepositoryInterface
{
    public function all(): Collection
    {
        return Especialidad::all();
    }

    public function find(int $id): ?Especialidad
    {
        return Especialidad::find($id);
    }

    public function create(array $data): Especialidad
    {
        return Especialidad::create($data);
    }

    public function update(Especialidad $especialidad, array $data): Especialidad
    {
        $especialidad->update($data);
        return $especialidad->fresh();
    }

    public function delete(Especialidad $especialidad): void
    {
        $especialidad->delete();
    }

    public function findWithProgramas(int $id): ?Especialidad
    {
        return Especialidad::with('programas')->find($id);
    }

    public function hasDependencies(int $id): bool
    {
        $especialidad = Especialidad::withCount('programas')->find($id);
        return $especialidad && $especialidad->programas_count > 0;
    }
}
