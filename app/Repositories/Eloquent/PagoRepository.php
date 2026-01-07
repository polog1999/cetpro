<?php

namespace App\Repositories\Eloquent;

use App\Models\Pago;
use App\Repositories\PagoRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PagoRepository implements PagoRepositoryInterface
{
    public function find(int $id): ?Pago
    {
        return Pago::find($id);
    }

    public function create(array $data): Pago
    {
        return Pago::create($data);
    }

    public function update(Pago $pago, array $data): Pago
    {
        $pago->update($data);
        return $pago->fresh();
    }

    public function delete(Pago $pago): void
    {
        $pago->delete();
    }

    public function findByCronograma(int $cronogramaId): Collection
    {
        return Pago::where('cronograma_id', $cronogramaId)
            ->orderBy('nro_cuota')
            ->get();
    }

    public function findPendientesByCronograma(int $cronogramaId): Collection
    {
        return Pago::where('cronograma_id', $cronogramaId)
            ->where(function ($q) {
                $q->whereRaw("LOWER(estado) LIKE '%pendiente%'")
                  ->orWhereRaw("LOWER(estado) LIKE '%vencido%'");
            })
            ->orderBy('nro_cuota')
            ->get();
    }

    public function findVencidosByCronograma(int $cronogramaId): Collection
    {
        return Pago::where('cronograma_id', $cronogramaId)
            ->whereRaw("LOWER(estado) LIKE '%vencido%'")
            ->orderBy('fecha_vencimiento')
            ->get();
    }

    public function getProximoPago(int $cronogramaId): ?Pago
    {
        return Pago::where('cronograma_id', $cronogramaId)
            ->where(function ($q) {
                $q->whereRaw("LOWER(estado) LIKE '%pendiente%'")
                  ->orWhereRaw("LOWER(estado) LIKE '%vencido%'");
            })
            ->orderBy('nro_cuota')
            ->first();
    }
}
