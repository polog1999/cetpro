<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Cronograma;
use App\Repositories\PagoRepositoryInterface;
use App\Repositories\CronogramaRepositoryInterface;
use App\Services\OracleTusneService;
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

        // Intentar anular también en Oracle si existe num_liquidacion
        if (!empty($pago->num_liquidacion)) {
            try {
                $oracleService = app(OracleTusneService::class);

                if ($oracleService->verificarConexion()) {
                    $oracleService->anularLiquidacion($pago->num_liquidacion);
                } else {
                    \Log::warning('Oracle no disponible al anular pago', ['pago_id' => $pago->id, 'num_liquidacion' => $pago->num_liquidacion]);
                }
            } catch (\Exception $e) {
                // No detener el flujo local por fallos en Oracle, solo loguear
                \Log::error('Error al anular liquidación en Oracle: ' . $e->getMessage(), ['pago_id' => $pago->id, 'num_liquidacion' => $pago->num_liquidacion]);
            }
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

    /**
     * Sincroniza los estados de pago desde Oracle.
     * 
     * Consulta VU_BUSCA_TUSNE_PER_Pen.ESTADO usando el num_liquidacion
     * de cada pago y actualiza el estado local si difiere.
     *
     * @param int $cronogramaId
     * @return array ['sincronizados' => int, 'errores' => int]
     */
    public function sincronizarEstadosDesdeOracle(int $cronogramaId): array
    {
        $resultado = ['sincronizados' => 0, 'errores' => 0];

        try {
            $oracleService = app(OracleTusneService::class);
            
            // Verificar conexión Oracle antes de proceder
            if (!$oracleService->verificarConexion()) {
                \Log::warning('Oracle no disponible para sincronizar estados de pago', [
                    'cronograma_id' => $cronogramaId,
                ]);
                return $resultado;
            }

            // Obtener pagos del cronograma que tienen num_liquidacion
            $pagos = Pago::where('cronograma_id', $cronogramaId)
                ->whereNotNull('num_liquidacion')
                ->where('num_liquidacion', '!=', '')
                ->get();

            foreach ($pagos as $pago) {
                try {
                    $estadoOracle = $oracleService->obtenerEstadoLiquidacion($pago->num_liquidacion);
                    
                    if ($estadoOracle && $estadoOracle !== $pago->estado) {
                        $this->pagos->update($pago, ['estado' => $estadoOracle]);
                        $resultado['sincronizados']++;
                        
                        \Log::info('Estado de pago sincronizado desde Oracle', [
                            'pago_id' => $pago->id,
                            'num_liquidacion' => $pago->num_liquidacion,
                            'estado_anterior' => $pago->estado,
                            'estado_nuevo' => $estadoOracle,
                        ]);
                    }
                } catch (\Exception $e) {
                    $resultado['errores']++;
                    \Log::warning('Error sincronizando pago individual desde Oracle', [
                        'pago_id' => $pago->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error general en sincronización Oracle de pagos', [
                'cronograma_id' => $cronogramaId,
                'error' => $e->getMessage(),
            ]);
        }

        return $resultado;
    }
}
