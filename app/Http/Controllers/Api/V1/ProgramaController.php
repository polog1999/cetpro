<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\ProgramaResource;
use App\Models\Programa;
use Illuminate\Http\JsonResponse;

class ProgramaController extends ApiController
{
    // Nota: ProgramaService no está creado todavía, usar modelo directamente por ahora
    // TODO: Inyectar ProgramaService cuando esté implementado

    public function index(): JsonResponse
    {
        $programas = Programa::with(['especialidad'])->get();
        return $this->success(ProgramaResource::collection($programas));
    }

    public function show(int $id): JsonResponse
    {
        $programa = Programa::with(['especialidad', 'cursos', 'horarios'])->find($id);

        if (!$programa) {
            return $this->error('Programa no encontrado', [], 404);
        }

        return $this->success(new ProgramaResource($programa));
    }

    public function store(\App\Http\Requests\Api\V1\StoreProgramaRequest $request): JsonResponse
    {
        $programa = Programa::create($request->validated());
        return $this->created(new ProgramaResource($programa));
    }

    public function update(\App\Http\Requests\Api\V1\UpdateProgramaRequest $request, int $id): JsonResponse
    {
        $programa = Programa::find($id);

        if (!$programa) {
            return $this->error('Programa no encontrado', [], 404);
        }

        $programa->update($request->validated());
        return $this->success(new ProgramaResource($programa->fresh()));
    }

    public function destroy(int $id): JsonResponse
    {
        $programa = Programa::find($id);

        if (!$programa) {
            return $this->error('Programa no encontrado', [], 404);
        }

        // Verificar dependencias
        if ($programa->horarios()->exists() || $programa->cursos()->exists()) {
            return $this->error('No se puede eliminar el programa porque tiene horarios o cursos asociados', [], 422);
        }

        $programa->delete();
        return $this->noContent();
    }
}
