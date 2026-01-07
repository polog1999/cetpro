<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Cronograma;
use App\Repositories\PagoRepositoryInterface;
use App\Repositories\CronogramaRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de lógica de negocio para Pagos.
 * 
 * Responsabilidad: Gestión de pagos de cuotas, incluyendo
 * registro, anulación, reversión y actualización de estados.
 * 
 * NOTA: El estado de pago ahora viene de Oracle (VU_BUSCA_TUSNE_PER_Pen.ESTADO)
 */
class PagoService
{
    public function __construct(
        private PagoRepositoryInterface $pagos,
        private CronogramaRepositoryInterface $cronogramas
    ) {}

    /**
     * Registra el pago de una cuota.
     * El estado real se sincroniza desde Oracle.
     */
    public function registrarPago(
        Pago $pago,
        string $metodoPago,
        ?string $evidenciaPath = null,
        ?int $usuarioId = null
    ): void {
        // Validar que se pueda pagar
        if (!$pago->puedeSerPagado()) {
            throw ValidationException::withMessages([
                'estado' => "No se puede pagar una cuota con estado: {$pago->estado}",
            ]);
        }

        DB::transaction(function () use ($pago, $metodoPago, $evidenciaPath, $usuarioId) {
            // Actualizar el pago (el estado real se sincroniza desde Oracle)
            $this->pagos->update($pago, [
                'fecha_pago' => now(),
                'metodo_pago' => $metodoPago,
                'evidencia_path' => $evidenciaPath,
                'usuario_id' => $usuarioId ?? auth()->id(),
            ]);

            // Actualizar el estado de la matrícula según el cronograma
            $cronograma = $this->cronogramas->findWithPagos($pago->cronograma_id);
            if ($cronograma && $cronograma->matricula) {
                $cronograma->matricula->actualizarEstadoSegunCronograma();
            }
        });
    }

    /**
     * Anula un pago.
     */
    public function anularPago(Pago $pago): void
    {
        if (str_contains(strtolower($pago->estado ?? ''), 'anulado')) {
            throw ValidationException::withMessages([
                'estado' => 'Esta cuota ya está anulada.',
            ]);
        }

        $this->pagos->update($pago, [
            'estado' => 'Anulado',
        ]);
    }

    /**
     * Revierte un pago (requiere permisos especiales).
     */
    public function revertirPago(Pago $pago, string $motivo): void
    {
        if (!str_contains(strtolower($pago->estado ?? ''), 'cancelado')) {
            throw ValidationException::withMessages([
                'estado' => 'Solo se pueden revertir pagos que estén en estado PAGADO.',
            ]);
        }

        DB::transaction(function () use ($pago) {
            $this->pagos->update($pago, [
                'estado' => 'Pendiente',
                'fecha_pago' => null,
                'metodo_pago' => null,
                'evidencia_path' => null,
            ]);

            // Actualizar el estado de la matrícula
            $cronograma = $this->cronogramas->findWithPagos($pago->cronograma_id);
            if ($cronograma && $cronograma->matricula) {
                $cronograma->matricula->actualizarEstadoSegunCronograma();
            }
        });
    }

    /**
     * Actualiza estados vencidos de un cronograma.
     * Ya no aplica porque el estado viene de Oracle.
     */
    public function actualizarEstadosVencidos(Cronograma $cronograma): int
    {
        // El estado se gestiona desde Oracle, no localmente
        return 0;
    }

    /**
     * Anula todos los pagos pendientes de un cronograma.
     * Útil al anular una matrícula.
     */
    public function anularPagosPendientes(int $cronogramaId): void
    {
        $pagosPendientes = $this->pagos->findPendientesByCronograma($cronogramaId);
        
        foreach ($pagosPendientes as $pago) {
            if (str_contains(strtolower($pago->estado ?? ''), 'pendiente')) {
                $this->pagos->update($pago, ['estado' => 'Anulado']);
            }
        }
    }

    /**
     * Obtiene el próximo pago pendiente de un cronograma.
     */
    public function obtenerProximoPago(int $cronogramaId): ?Pago
    {
        return $this->pagos->getProximoPago($cronogramaId);
    }
}
