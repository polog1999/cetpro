<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\EmpleadoService;
use App\Http\Requests\Api\V1\StoreEmpleadoRequest;
use App\Http\Requests\Api\V1\UpdateEmpleadoRequest;
use App\Http\Resources\Api\V1\EmpleadoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class EmpleadoController extends ApiController
{
    public function __construct(
        private EmpleadoService $empleadoService
    ) {}

    public function index(): JsonResponse
    {
        $empleados = $this->empleadoService->obtenerTodos();
        return $this->success(EmpleadoResource::collection($empleados));
    }

    public function show(int $id): JsonResponse
    {
        $empleado = $this->empleadoService->buscar($id);

        if (!$empleado) {
            return $this->error('Empleado no encontrado', [], 404);
        }

        return $this->success(new EmpleadoResource($empleado));
    }

    public function store(StoreEmpleadoRequest $request): JsonResponse
    {
        try {
            $empleado = $this->empleadoService->crear($request->validated());
            return $this->created(new EmpleadoResource($empleado));
        } catch (ValidationException $e) {
            return $this->error('Error de validación', $e->errors(), 422);
        }
    }

    public function update(UpdateEmpleadoRequest $request, int $id): JsonResponse
    {
        try {
            $empleado = $this->empleadoService->actualizar($id, $request->validated());
            return $this->success(new EmpleadoResource($empleado));
        } catch (ValidationException $e) {
            return $this->error('Error de validación', $e->errors(), 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->empleadoService->eliminar($id);
            return $this->noContent();
        } catch (ValidationException $e) {
            return $this->error('No se puede eliminar', $e->errors(), 422);
        }
    }
}
