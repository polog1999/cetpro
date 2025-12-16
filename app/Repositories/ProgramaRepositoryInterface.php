<?php

namespace App\Repositories;

use App\Models\Programa;
use Illuminate\Database\Eloquent\Collection;

interface ProgramaRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Programa;
    public function create(array $data): Programa;
    public function update(Programa $programa, array $data): Programa;
    public function delete(Programa $programa): void;
    public function findWithCursos(int $id): ?Programa;
    public function findWithHorarios(int $id): ?Programa;
    public function findByEspecialidad(int $especialidadId): Collection;
    public function hasDependencies(int $id): bool;
}
