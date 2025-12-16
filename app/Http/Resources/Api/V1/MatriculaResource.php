<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatriculaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo_inscripcion' => $this->codigo_inscripcion,
            'estudiante_id' => $this->estudiante_id,
            'estudiante' => $this->whenLoaded('estudiante', function () {
                return new EstudianteResource($this->estudiante);
            }),
            'tipo_matricula' => $this->tipo_matricula,
            'horario_id' => $this->horario_id,
            'horario' => $this->whenLoaded('horario', function () {
                return new HorarioResource($this->horario);
            }),
            'id_curso' => $this->id_curso,
            'estado' => $this->estado,
            'fecha_matricula' => $this->fecha_matricula?->format('Y-m-d'),
            'cronograma' => $this->whenLoaded('cronograma', function () {
                return new CronogramaResource($this->cronograma);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
