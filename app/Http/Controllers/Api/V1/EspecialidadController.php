<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\EspecialidadResource;
use App\Models\Especialidad;
use Illuminate\Http\JsonResponse;

class EspecialidadController extends ApiController
{
    // TODO: Inyectar EspecialidadService cuando esté implementado

    public function index(): JsonResponse
    {
        $especialidades = Especialidad::all();
        return $this->success(EspecialidadResource::collection($especialidades));
    }

    public function show(int $id): JsonResponse
    {
        $especialidad = Especialidad::with('programas')->find($id);

        if (!$especialidad) {
            return $this->error('Especialidad no encontrada', [], 404);
        }

        return $this->success(new EspecialidadResource($especialidad));
    }

    public function store(\App\Http\Requests\Api\V1\StoreEspecialidadRequest $request): JsonResponse
    {
        $especialidad = Especialidad::create($request->validated());
        return $this->created(new EspecialidadResource($especialidad));
    }

    public function update(\App\Http\Requests\Api\V1\UpdateEspecialidadRequest $request, int $id): JsonResponse
    {
        $especialidad = Especialidad::find($id);

        if (!$especialidad) {
            return $this->error('Especialidad no encontrada', [], 404);
        }

        $especialidad->update($request->validated());
        return $this->success(new EspecialidadResource($especialidad->fresh()));
    }

    public function destroy(int $id): JsonResponse
    {
        $especialidad = Especialidad::find($id);

        if (!$especialidad) {
            return $this->error('Especialidad no encontrada', [], 404);
        }

        if ($especialidad->programas()->exists()) {
            return $this->error('No se puede eliminar la especialidad porque tiene programas asociados', [], 422);
        }

        $especialidad->delete();
        return $this->noContent();
    }
}
