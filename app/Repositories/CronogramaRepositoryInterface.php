<?php

namespace App\Repositories;

use App\Models\Cronograma;
use Illuminate\Database\Eloquent\Collection;

interface CronogramaRepositoryInterface
{
    public function find(int $id): ?Cronograma;
    public function findByMatricula(int $matriculaId): ?Cronograma;
    public function create(array $data): Cronograma;
    public function update(Cronograma $cronograma, array $data): Cronograma;
    public function delete(Cronograma $cronograma): void;
    public function findWithPagos(int $id): ?Cronograma;
    public function getCronogramasConDeuda(): Collection;
}
