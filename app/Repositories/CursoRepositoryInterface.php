<?php

namespace App\Repositories;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Collection;

interface CursoRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Curso;
    public function create(array $data): Curso;
    public function update(Curso $curso, array $data): Curso;
    public function delete(Curso $curso): void;
    public function findByPrograma(int $programaId): Collection;
    public function hasDependencies(int $id): bool;
}
