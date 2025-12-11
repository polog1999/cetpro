<?php

namespace App\Repositories\Eloquent;

use App\Models\Horario;
use App\Repositories\HorarioRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class HorarioRepository implements HorarioRepositoryInterface
{
    public function all(): Collection
    {
        return Horario::all();
    }

    public function find(int $id): ?Horario
    {
        return Horario::find($id);
    }

    public function create(array $data): Horario
    {
        return Horario::create($data);
    }

    public function update(Horario $horario, array $data): Horario
    {
        $horario->update($data);
        return $horario->fresh();
    }

    public function delete(Horario $horario): void
    {
        $horario->delete();
    }

    public function findByPrograma(int $programaId): Collection
    {
        return Horario::where('id_programa', $programaId)->get();
    }

    public function findActivos(): Collection
    {
        return Horario::where('activo', true)->get();
    }

    public function hasDependencies(int $id): bool
    {
        $horario = Horario::withCount('matriculas')->find($id);
        return $horario && $horario->matriculas_count > 0;
    }
}
