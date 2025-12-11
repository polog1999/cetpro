<?php

namespace App\Repositories;

use App\Models\Especialidad;
use Illuminate\Database\Eloquent\Collection;

interface EspecialidadRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Especialidad;
    public function create(array $data): Especialidad;
    public function update(Especialidad $especialidad, array $data): Especialidad;
    public function delete(Especialidad $especialidad): void;
    public function findWithProgramas(int $id): ?Especialidad;
    public function hasDependencies(int $id): bool;
}
