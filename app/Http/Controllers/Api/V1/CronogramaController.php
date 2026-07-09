<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\PagoService;
use App\Http\Resources\Api\V1\CronogramaResource;
use App\Http\Resources\Api\V1\PagoResource;
use App\Models\Cronograma;
use App\Models\Matricula;
use Barryvdh\DomPDF\Facade\Pdf;
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
   public function verCronograma(Matricula $matricula)
{
    $matricula->load(['estudiante', 'horario.programa', 'curso', 'cronograma.pagos' => function ($query) {
        $query->orderBy('nro_cuota', 'asc');
    }]);

    $pdf = Pdf::loadView('matriculas.cronograma-pdf', [
        'matricula' => $matricula,
    ])
        ->setPaper('A4', 'portrait');

    $fileName = 'cronograma-pagos-' . ($matricula->codigo_inscripcion ?? $matricula->id) . '.pdf';

    //  RETORNA DIRECTAMENTE EL STREAM
    // El segundo parámetro son las opciones; 'attachment' => false le dice que NO lo descargue (usa inline)
    return $pdf->stream($fileName, ['attachment' => false]);
}
}
