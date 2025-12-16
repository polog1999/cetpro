<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\ApoderadoService;
use App\Http\Requests\Api\V1\StoreApoderadoRequest;
use App\Http\Requests\Api\V1\UpdateApoderadoRequest;
use App\Http\Resources\Api\V1\ApoderadoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ApoderadoController extends ApiController
{
    public function __construct(
        private ApoderadoService $apoderadoService
    ) {}

    public function index(): JsonResponse
    {
        $apoderados = $this->apoderadoService->obtenerTodos();
        return $this->success(ApoderadoResource::collection($apoderados));
    }

    public function show(int $id): JsonResponse
    {
        $apoderado = $this->apoderadoService->buscar($id);

        if (!$apoderado) {
            return $this->error('Apoderado no encontrado', [], 404);
        }

        return $this->success(new ApoderadoResource($apoderado));
    }

    public function store(StoreApoderadoRequest $request): JsonResponse
    {
        try {
            $apoderado = $this->apoderadoService->crear($request->validated());
            return $this->created(new ApoderadoResource($apoderado));
        } catch (ValidationException $e) {
            return $this->error('Error de validación', $e->errors(), 422);
        }
    }

    public function update(UpdateApoderadoRequest $request, int $id): JsonResponse
    {
        try {
            $apoderado = $this->apoderadoService->actualizar($id, $request->validated());
            return $this->success(new ApoderadoResource($apoderado));
        } catch (ValidationException $e) {
            return $this->error('Error de validación', $e->errors(), 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->apoderadoService->eliminar($id);
            return $this->noContent();
        } catch (ValidationException $e) {
            return $this->error('No se puede eliminar', $e->errors(), 422);
        }
    }
}
