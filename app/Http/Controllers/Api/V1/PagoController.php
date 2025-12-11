<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\PagoService;
use App\Http\Requests\Api\V1\RegistrarPagoRequest;
use App\Http\Requests\Api\V1\RevertirPagoRequest;
use App\Http\Resources\Api\V1\PagoResource;
use App\Models\Pago;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PagoController extends ApiController
{
    public function __construct(
        private PagoService $pagoService
    ) {}

    public function show(int $id): JsonResponse
    {
        $pago = Pago::with(['cronograma.matricula.estudiante'])->find($id);

        if (!$pago) {
            return $this->error('Pago no encontrado', [], 404);
        }

        return $this->success(new PagoResource($pago));
    }

    /**
     * Registrar pago de una cuota.
     */
    public function registrar(RegistrarPagoRequest $request, int $id): JsonResponse
    {
        $pago = Pago::find($id);

        if (!$pago) {
            return $this->error('Pago no encontrado', [], 404);
        }

        try {
            $this->pagoService->registrarPago(
                pago: $pago,
                metodoPago: $request->metodo_pago,
                evidenciaPath: $request->evidencia_path ?? null,
                usuarioId: $request->usuario_id ?? auth()->id()
            );

            return $this->success([
                'message' => 'Pago registrado exitosamente',
                'pago' => new PagoResource($pago->fresh()),
            ]);
        } catch (ValidationException $e) {
            return $this->error('Error al registrar pago', $e->errors(), 422);
        }
    }

    /**
     * Anular pago.
     */
    public function anular(int $id): JsonResponse
    {
        $pago = Pago::find($id);

        if (!$pago) {
            return $this->error('Pago no encontrado', [], 404);
        }

        try {
            $this->pagoService->anularPago($pago);

            return $this->success([
                'message' => 'Pago anulado exitosamente',
                'pago' => new PagoResource($pago->fresh()),
            ]);
        } catch (ValidationException $e) {
            return $this->error('Error al anular pago', $e->errors(), 422);
        }
    }

    /**
     * Revertir pago (requiere permisos especiales).
     */
    public function revertir(RevertirPagoRequest $request, int $id): JsonResponse
    {
        $pago = Pago::find($id);

        if (!$pago) {
            return $this->error('Pago no encontrado', [], 404);
        }

        try {
            $this->pagoService->revertirPago($pago, $request->motivo);

            return $this->success([
                'message' => 'Pago revertido exitosamente',
                'pago' => new PagoResource($pago->fresh()),
            ]);
        } catch (ValidationException $e) {
            return $this->error('Error al revertir pago', $e->errors(), 422);
        }
    }
}
