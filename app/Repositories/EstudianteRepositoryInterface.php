<?php

namespace App\Repositories;

use App\Models\Estudiante;
use Illuminate\Database\Eloquent\Collection;

interface EstudianteRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Estudiante;
    public function create(array $data): Estudiante;
    public function update(Estudiante $estudiante, array $data): Estudiante;
    public function delete(Estudiante $estudiante): void;
    
    public function findByDocumento(string $tipoDocumento, string $nroDocumento): ?Estudiante;
    public function findWithMatriculas(int $id): ?Estudiante;
    public function findWithApoderado(int $id): ?Estudiante;
    public function searchByNombre(string $search): Collection;
}
