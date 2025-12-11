<?php

namespace App\Repositories\Eloquent;

use App\Models\Programa;
use App\Repositories\ProgramaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProgramaRepository implements ProgramaRepositoryInterface
{
    public function all(): Collection
    {
        return Programa::all();
    }

    public function find(int $id): ?Programa
    {
        return Programa::find($id);
    }

    public function create(array $data): Programa
    {
        return Programa::create($data);
    }

    public function update(Programa $programa, array $data): Programa
    {
        $programa->update($data);
        return $programa->fresh();
    }

    public function delete(Programa $programa): void
    {
        $programa->delete();
    }

    public function findWithCursos(int $id): ?Programa
    {
        return Programa::with('cursos')->find($id);
    }

    public function findWithHorarios(int $id): ?Programa
    {
        return Programa::with('horarios')->find($id);
    }

    public function findByEspecialidad(int $especialidadId): Collection
    {
        return Programa::where('id_especialidad', $especialidadId)->get();
    }

    public function hasDependencies(int $id): bool
    {
        $programa = Programa::withCount(['cursos', 'horarios'])->find($id);
        return $programa && ($programa->cursos_count > 0 || $programa->horarios_count > 0);
    }
}
