<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\CursoResource;
use App\Models\Curso;
use Illuminate\Http\JsonResponse;

class CursoController extends ApiController
{
    // TODO: Inyectar CursoService cuando esté implementado

    public function index(): JsonResponse
    {
        $cursos = Curso::with('programa')->get();
        return $this->success(CursoResource::collection($cursos));
    }

    public function show(int $id): JsonResponse
    {
        $curso = Curso::with('programa')->find($id);

        if (!$curso) {
            return $this->error('Curso no encontrado', [], 404);
        }

        return $this->success(new CursoResource($curso));
    }

    public function store(\App\Http\Requests\Api\V1\StoreCursoRequest $request): JsonResponse
    {
        $curso = Curso::create($request->validated());
        return $this->created(new CursoResource($curso));
    }

    public function update(\App\Http\Requests\Api\V1\UpdateCursoRequest $request, int $id): JsonResponse
    {
        $curso = Curso::find($id);

        if (!$curso) {
            return $this->error('Curso no encontrado', [], 404);
        }

        $curso->update($request->validated());
        return $this->success(new CursoResource($curso->fresh()));
    }

    public function destroy(int $id): JsonResponse
    {
        $curso = Curso::find($id);

        if (!$curso) {
            return $this->error('Curso no encontrado', [], 404);
        }

        // Verificar si tiene matrículas
        if (\App\Models\Matricula::where('id_curso', $id)->exists()) {
            return $this->error('No se puede eliminar el curso porque tiene matrículas asociadas', [], 422);
        }

        $curso->delete();
        return $this->noContent();
    }
}
