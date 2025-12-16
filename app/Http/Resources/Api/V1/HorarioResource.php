<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HorarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_horario' => $this->id_horario,
            'codigo' => $this->codigo,
            'id_programa' => $this->id_programa,
            'programa' => $this->whenLoaded('programa', function () {
                return new ProgramaResource($this->programa);
            }),
            'id_docente' => $this->id_docente,
            'docente' => $this->whenLoaded('docente', function () {
                return new DocenteResource($this->docente);
            }),
            'turno' => $this->turno,
            'dias' => $this->dias,
            'hora_inicio' => $this->hora_inicio,
            'hora_fin' => $this->hora_fin,
            'fecha_inicio' => $this->fecha_inicio?->format('Y-m-d'),
            'fecha_fin' => $this->fecha_fin?->format('Y-m-d'),
            'vacantes' => $this->vacantes,
            'activo' => $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
