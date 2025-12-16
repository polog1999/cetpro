<?php

namespace App\Repositories;

use App\Models\Pago;
use Illuminate\Database\Eloquent\Collection;

interface PagoRepositoryInterface
{
    public function find(int $id): ?Pago;
    public function create(array $data): Pago;
    public function update(Pago $pago, array $data): Pago;
    public function delete(Pago $pago): void;
    public function findByCronograma(int $cronogramaId): Collection;
    public function findPendientesByCronograma(int $cronogramaId): Collection;
    public function findVencidosByCronograma(int $cronogramaId): Collection;
    public function getProximoPago(int $cronogramaId): ?Pago;
}
