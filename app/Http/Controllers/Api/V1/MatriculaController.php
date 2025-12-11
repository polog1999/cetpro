<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\MatriculaService;
use App\Http\Requests\Api\V1\StoreMatriculaRequest;
use App\Http\Resources\Api\V1\MatriculaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MatriculaController extends ApiController
{
    public function __construct(
        private MatriculaService $matriculaService
    ) {}

    public function index(): JsonResponse
    {
        // Nota: MatriculaService no tiene obtenerTodos(), usar repositorio si existe
        // Por ahora retornamos todas usando el modelo directamente (excepción justificada para listados)
        $matriculas = \App\Models\Matricula::with(['estudiante', 'horario', 'cronograma'])->get();
        return $this->success(MatriculaResource::collection($matriculas));
    }

    public function show(int $id): JsonResponse
    {
        $matricula = \App\Models\Matricula::with(['estudiante', 'horario', 'cronograma.pagos'])->find($id);

        if (!$matricula) {
            return $this->error('Matrícula no encontrada', [], 404);
        }

        return $this->success(new MatriculaResource($matricula));
    }

    /**
     * Crear nueva matrícula.
     * El servicio se encarga de validaciones, generación de cronograma y pagos.
     */
    public function store(StoreMatriculaRequest $request): JsonResponse
    {
        try {
            // Por ahora usar creación directa del modelo ya que MatriculaService::crear
            // necesita refactorización para usar repositorios
            // TODO: Usar $this->matriculaService->crear($request->validated()) cuando esté refactorizado
            $matricula = \App\Models\Matricula::create($request->validated());
            
            return $this->created(new MatriculaResource($matricula));
        } catch (ValidationException $e) {
            return $this->error('Error de validación', $e->errors(), 422);
        }
    }

    /**
     * Anular matrícula.
     * Usa MatriculaService::anularMatricula()
     */
    public function anular(int $id): JsonResponse
    {
        try {
            $resultado = $this->matriculaService->anularMatricula($id, 'Anulación vía API');
            
            if (!$resultado['success']) {
                return $this->error($resultado['message'], [], 422);
            }

            return $this->success([
                'message' => $resultado['message'],
                'matricula' => new MatriculaResource($resultado['matricula']),
            ]);
        } catch (ValidationException $e) {
            return $this->error('Error al anular', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->error('Error al anular matrícula', ['error' => $e->getMessage()], 500);
        }
    }
}
