<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstudianteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo_modular' => $this->codigo_modular,
            'tipo_documento' => $this->tipo_documento,
            'nro_documento' => $this->nro_documento,
            'nombres' => $this->nombres,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'nombre_completo' => $this->nombres . ' ' . $this->apellido_paterno . ' ' . $this->apellido_materno,
            'fecha_nacimiento' => $this->fecha_nacimiento?->format('Y-m-d'),
            'edad' => $this->edad, // accessor del modelo
            'sexo' => $this->sexo,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'apoderado_id' => $this->apoderado_id,
            'apoderado' => $this->whenLoaded('apoderado', function () {
                return new ApoderadoResource($this->apoderado);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
