<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'id_especialidad' => $this->id_especialidad,
            'tipo' => $this->tipo,
            'duracion' => $this->duracion,
            'descripcion' => $this->descripcion,
            'especialidad' => $this->whenLoaded('especialidad', function () {
                return new EspecialidadResource($this->especialidad);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
