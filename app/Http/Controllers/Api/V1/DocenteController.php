<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\DocenteService;
use App\Http\Requests\Api\V1\StoreDocenteRequest;
use App\Http\Requests\Api\V1\UpdateDocenteRequest;
use App\Http\Resources\Api\V1\DocenteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class DocenteController extends ApiController
{
    public function __construct(
        private DocenteService $docenteService
    ) {}

    /**
     * Listar todos los docentes.
     */
    public function index(): JsonResponse
    {
        $docentes = $this->docenteService->obtenerTodos();
        return $this->success(DocenteResource::collection($docentes));
    }

    /**
     * Obtener detalle de un docente.
     */
    public function show(int $id): JsonResponse
    {
        $docente = $this->docenteService->buscar($id);

        if (!$docente) {
            return $this->error('Docente no encontrado', [], 404);
        }

        return $this->success(new DocenteResource($docente));
    }

    /**
     * Crear nuevo docente.
     */
    public function store(StoreDocenteRequest $request): JsonResponse
    {
        try {
            $docente = $this->docenteService->crear($request->validated());
            return $this->created(new DocenteResource($docente));
        } catch (ValidationException $e) {
            return $this->error('Error de validación', $e->errors(), 422);
        }
    }

    /**
     * Actualizar docente.
     */
    public function update(UpdateDocenteRequest $request, int $id): JsonResponse
    {
        try {
            $docente = $this->docenteService->actualizar($id, $request->validated());
            return $this->success(new DocenteResource($docente));
        } catch (ValidationException $e) {
            return $this->error('Error de validación', $e->errors(), 422);
        }
    }

    /**
     * Eliminar docente.
     * Valida que no tenga horarios asignados.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->docenteService->eliminar($id);
            return $this->noContent();
        } catch (ValidationException $e) {
            return $this->error('No se puede eliminar', $e->errors(), 422);
        }
    }
}
