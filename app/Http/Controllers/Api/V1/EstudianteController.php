<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\EstudianteService;
use App\Http\Requests\Api\V1\StoreEstudianteRequest;
use App\Http\Requests\Api\V1\UpdateEstudianteRequest;
use App\Http\Resources\Api\V1\EstudianteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class EstudianteController extends ApiController
{
    public function __construct(
        private EstudianteService $estudianteService
    ) {}

    /**
     * Listado de estudiantes.
     */
    public function index(): JsonResponse
    {
        $estudiantes = $this->estudianteService->obtenerTodos();
        return $this->success(EstudianteResource::collection($estudiantes));
    }

    /**
     * Detalle de un estudiante.
     */
    public function show(int $id): JsonResponse
    {
        $estudiante = $this->estudianteService->buscar($id);

        if (!$estudiante) {
            return $this->error('Estudiante no encontrado', [], 404);
        }

        return $this->success(new EstudianteResource($estudiante));
    }

    /**
     * Crear nuevo estudiante.
     */
    public function store(StoreEstudianteRequest $request): JsonResponse
    {
        try {
            $estudiante = $this->estudianteService->crear($request->validated());
            return $this->created(new EstudianteResource($estudiante));
        } catch (ValidationException $e) {
            return $this->error('Error de validación', $e->errors(), 422);
        }
    }

    /**
     * Actualizar estudiante.
     */
    public function update(UpdateEstudianteRequest $request, int $id): JsonResponse
    {
        try {
            $estudiante = $this->estudianteService->actualizar($id, $request->validated());
            return $this->success(new EstudianteResource($estudiante));
        } catch (ValidationException $e) {
            return $this->error('Error de validación', $e->errors(), 422);
        }
    }

    /**
     * Eliminar estudiante.
     * Valida que no tenga matrículas activas.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->estudianteService->eliminar($id);
            return $this->noContent();
        } catch (ValidationException $e) {
            return $this->error('No se puede eliminar', $e->errors(), 422);
        }
    }
}
