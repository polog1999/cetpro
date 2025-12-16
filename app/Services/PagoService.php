<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Cronograma;
use App\Enums\EstadoPago;
use App\Repositories\PagoRepositoryInterface;
use App\Repositories\CronogramaRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de lógica de negocio para Pagos.
 * 
 * Responsabilidad: Gestión de pagos de cuotas, incluyendo
 * registro, anulación, reversión y actualización de estados.
 */
class PagoService
{
    public function __construct(
        private PagoRepositoryInterface $pagos,
        private CronogramaRepositoryInterface $cronogramas
    ) {}

    /**
     * Registra el pago de una cuota.
     * Esta lógica fue movida desde Pago::registrarPago()
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
                'estado' => "No se puede pagar una cuota en estado: {$pago->estado->getLabel()}",
            ]);
        }

        DB::transaction(function () use ($pago, $metodoPago, $evidenciaPath, $usuarioId) {
            // Actualizar el pago
            $this->pagos->update($pago, [
                'estado' => EstadoPago::PAGADO,
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
     * Lógica movida desde Pago::anular()
     */
    public function anularPago(Pago $pago): void
    {
        if ($pago->estado === EstadoPago::ANULADO) {
            throw ValidationException::withMessages([
                'estado' => 'Esta cuota ya está anulada.',
            ]);
        }

        $this->pagos->update($pago, [
            'estado' => EstadoPago::ANULADO,
        ]);
    }

    /**
     * Revierte un pago (requiere permisos especiales).
     * Lógica movida desde Pago::revertirPago()
     */
    public function revertirPago(Pago $pago, string $motivo): void
    {
        if ($pago->estado !== EstadoPago::PAGADO) {
            throw ValidationException::withMessages([
                'estado' => 'Solo se pueden revertir pagos que estén en estado PAGADO.',
            ]);
        }

        DB::transaction(function () use ($pago) {
            // Cambiar a pendiente o vencido según la fecha
            $nuevoEstado = $pago->fecha_vencimiento < now()->startOfDay()
                ? EstadoPago::VENCIDO
                : EstadoPago::PENDIENTE;

            $this->pagos->update($pago, [
                'estado' => $nuevoEstado,
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
     * Lógica movida desde Cronograma::actualizarEstadosVencidos()
     */
    public function actualizarEstadosVencidos(Cronograma $cronograma): int
    {
        $pagosPendientes = $this->pagos->findPendientesByCronograma($cronograma->id);
        
        $actualizados = 0;
        foreach ($pagosPendientes as $pago) {
            if ($pago->estado === EstadoPago::PENDIENTE && $pago->fecha_vencimiento < now()->startOfDay()) {
                $this->pagos->update($pago, ['estado' => EstadoPago::VENCIDO]);
                $actualizados++;
            }
        }

        return $actualizados;
    }

    /**
     * Anula todos los pagos pendientes de un cronograma.
     * Útil al anular una matrícula.
     */
    public function anularPagosPendientes(int $cronogramaId): void
    {
        $pagosPendientes = $this->pagos->findPendientesByCronograma($cronogramaId);
        
        foreach ($pagosPendientes as $pago) {
            if ($pago->estado === EstadoPago::PENDIENTE) {
                $this->pagos->update($pago, ['estado' => EstadoPago::ANULADO]);
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
