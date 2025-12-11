<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CronogramaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'matricula_id' => $this->matricula_id,
            'num_cuotas' => $this->num_cuotas,
            'monto_total' => $this->monto_total,
            'pagos' => $this->whenLoaded('pagos', function () {
                return PagoResource::collection($this->pagos);
            }),
            'total_pagado' => $this->totalPagado(), // método del modelo
            'tiene_deuda' => $this->tieneDeuda(), // método del modelo
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
