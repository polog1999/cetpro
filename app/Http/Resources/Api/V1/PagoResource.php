<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'cronograma_id' => $this->cronograma_id,
            'nro_cuota' => $this->nro_cuota,
            'monto' => $this->monto,
            'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),
            'fecha_pago' => $this->fecha_pago?->format('Y-m-d H:i:s'),
            'metodo_pago' => $this->metodo_pago,
            'estado' => $this->estado,
            'evidencia_path' => $this->evidencia_path,
            'usuario_id' => $this->usuario_id,
            'dias_retraso' => $this->diasRetraso(), // método del modelo
            'esta_vencido' => $this->estaVencido(), // método del modelo
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
