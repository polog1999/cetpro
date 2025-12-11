<?php

namespace App\Repositories\Eloquent;

use App\Models\Cronograma;
use App\Enums\EstadoPago;
use App\Repositories\CronogramaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CronogramaRepository implements CronogramaRepositoryInterface
{
    public function find(int $id): ?Cronograma
    {
        return Cronograma::find($id);
    }

    public function findByMatricula(int $matriculaId): ?Cronograma
    {
        return Cronograma::where('matricula_id', $matriculaId)->first();
    }

    public function create(array $data): Cronograma
    {
        return Cronograma::create($data);
    }

    public function update(Cronograma $cronograma, array $data): Cronograma
    {
        $cronograma->update($data);
        return $cronograma->fresh();
    }

    public function delete(Cronograma $cronograma): void
    {
        $cronograma->delete();
    }

    public function findWithPagos(int $id): ?Cronograma
    {
        return Cronograma::with('pagos')->find($id);
    }

    public function getCronogramasConDeuda(): Collection
    {
        return Cronograma::whereHas('pagos', function ($query) {
            $query->whereIn('estado', [EstadoPago::PENDIENTE, EstadoPago::VENCIDO]);
        })->with('matricula.estudiante')->get();
    }
}
