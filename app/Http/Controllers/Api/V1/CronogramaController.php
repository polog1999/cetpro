<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\PagoService;
use App\Http\Resources\Api\V1\CronogramaResource;
use App\Http\Resources\Api\V1\PagoResource;
use App\Models\Cronograma;
use Illuminate\Http\JsonResponse;

class CronogramaController extends ApiController
{
    public function __construct(
        private PagoService $pagoService
    ) {}

    public function show(int $id): JsonResponse
    {
        $cronograma = Cronograma::with(['matricula.estudiante', 'pagos'])->find($id);

        if (!$cronograma) {
            return $this->error('Cronograma no encontrado', [], 404);
        }

        return $this->success(new CronogramaResource($cronograma));
    }

    /**
     * Listar pagos de un cronograma.
     */
    public function pagos(int $id): JsonResponse
    {
        $cronograma = Cronograma::with('pagos')->find($id);

        if (!$cronograma) {
            return $this->error('Cronograma no encontrado', [], 404);
        }

        return $this->success(PagoResource::collection($cronograma->pagos));
    }

    /**
     * Actualizar estados vencidos de un cronograma.
     */
    public function actualizarEstadosVencidos(int $id): JsonResponse
    {
        $cronograma = Cronograma::find($id);

        if (!$cronograma) {
            return $this->error('Cronograma no encontrado', [], 404);
        }

        $actualizados = $this->pagoService->actualizarEstadosVencidos($cronograma);

        return $this->success([
            'message' => "Se actualizaron {$actualizados} cuota(s) vencida(s)",
            'cuotas_actualizadas' => $actualizados,
        ]);
    }
}
