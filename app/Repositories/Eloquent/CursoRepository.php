<?php

namespace App\Repositories\Eloquent;

use App\Models\Curso;
use App\Repositories\CursoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CursoRepository implements CursoRepositoryInterface
{
    public function all(): Collection
    {
        return Curso::all();
    }

    public function find(int $id): ?Curso
    {
        return Curso::find($id);
    }

    public function create(array $data): Curso
    {
        return Curso::create($data);
    }

    public function update(Curso $curso, array $data): Curso
    {
        $curso->update($data);
        return $curso->fresh();
    }

    public function delete(Curso $curso): void
    {
        $curso->delete();
    }

    public function findByPrograma(int $programaId): Collection
    {
        return Curso::where('id_programa', $programaId)->get();
    }

    public function hasDependencies(int $id): bool
    {
        // Verificar si tiene matrículas
        return \App\Models\Matricula::where('id_curso', $id)->exists();
    }
}
