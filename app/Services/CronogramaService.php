<?php

namespace App\Services;

use App\Models\Cronograma;
use App\Models\Pago;
use App\Models\Matricula;
use App\Enums\EstadoPago;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Servicio para gestión de cronogramas de pago y cuotas.
 */
class CronogramaService
{
    /**
     * Actualiza todos los pagos pendientes que ya han vencido.
     *
     * @return int Número de pagos actualizados
     */
    public function actualizarPagosVencidos(): int
    {
        return Pago::where('estado', EstadoPago::PENDIENTE)
            ->where('fecha_vencimiento', '<', now()->startOfDay())
            ->update(['estado' => EstadoPago::VENCIDO]);
    }

    /**
     * Actualiza los pagos vencidos de un cronograma específico.
     *
     * @param int $cronogramaId
     * @return int Número de pagos actualizados
     */
    public function actualizarPagosVencidosDeCronograma(int $cronogramaId): int
    {
        $cronograma = Cronograma::findOrFail($cronogramaId);
        return $cronograma->actualizarEstadosVencidos();
    }

    /**
     * Registra un pago en una cuota específica.
     *
     * @param int $pagoId
     * @param string $metodoPago
     * @param string|null $evidenciaPath
     * @param string|null $numLiquidacion
     * @param string|null $fechaLiquidacion
     * @param int|null $usuarioId
     * @return Pago
     * @throws ValidationException
     */
    public function registrarPago(
        int $pagoId,
        string $metodoPago,
        ?string $evidenciaPath = null,
        ?string $numLiquidacion = null,
        ?string $fechaLiquidacion = null,
        ?int $usuarioId = null
    ): Pago {
        $pago = Pago::findOrFail($pagoId);

        DB::beginTransaction();
        try {
            $pago->registrarPago($metodoPago, $evidenciaPath, $usuarioId);

            // Actualizar datos de liquidación si se proporcionan
            if ($numLiquidacion || $fechaLiquidacion) {
                $pago->update([
                    'num_liquidacion' => $numLiquidacion,
                    'fecha_liquidacion' => $fechaLiquidacion,
                ]);
            }

            DB::commit();
            return $pago->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Anula un pago específico.
     *
     * @param int $pagoId
     * @return Pago
     * @throws ValidationException
     */
    public function anularPago(int $pagoId): Pago
    {
        $pago = Pago::findOrFail($pagoId);

        DB::beginTransaction();
        try {
            $pago->anular();
            DB::commit();
            return $pago->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Revierte un pago (requiere permisos especiales).
     *
     * @param int $pagoId
     * @param string $motivo
     * @return Pago
     * @throws ValidationException
     */
    public function revertirPago(int $pagoId, string $motivo): Pago
    {
        $pago = Pago::findOrFail($pagoId);

        DB::beginTransaction();
        try {
            $pago->revertirPago($motivo);
            DB::commit();
            return $pago->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene el resumen completo de un cronograma.
     *
     * @param int $cronogramaId
     * @return array
     */
    public function obtenerResumenCronograma(int $cronogramaId): array
    {
        $cronograma = Cronograma::with('pagos')->findOrFail($cronogramaId);
        return $cronograma->resumen();
    }

    /**
     * Obtiene todos los cronogramas con cuotas vencidas.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerCronogramasConCuotasVencidas()
    {
        return Cronograma::whereHas('pagos', function ($query) {
            $query->where('estado', EstadoPago::VENCIDO);
        })
        ->with(['matricula.estudiante', 'pagos' => function ($query) {
            $query->where('estado', EstadoPago::VENCIDO);
        }])
        ->get();
    }

    /**
     * Obtiene todos los estudiantes con pagos vencidos.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerEstudiantesConPagosVencidos()
    {
        return Matricula::whereHas('cronograma.pagos', function ($query) {
            $query->where('estado', EstadoPago::VENCIDO);
        })
        ->with(['estudiante', 'cronograma.pagos' => function ($query) {
            $query->where('estado', EstadoPago::VENCIDO);
        }])
        ->get();
    }

    /**
     * Calcula el monto total adeudado por un estudiante.
     *
     * @param int $estudianteId
     * @return float
     */
    public function calcularDeudaTotalEstudiante(int $estudianteId): float
    {
        return (float) Pago::whereHas('cronograma.matricula', function ($query) use ($estudianteId) {
            $query->where('estudiante_id', $estudianteId);
        })
        ->whereIn('estado', [EstadoPago::PENDIENTE, EstadoPago::VENCIDO])
        ->sum('monto');
    }

    /**
     * Genera estadísticas de pagos del sistema.
     *
     * @return array
     */
    public function obtenerEstadisticasPagos(): array
    {
        $totalPagos = Pago::count();
        $pagosPagados = Pago::where('estado', EstadoPago::PAGADO)->count();
        $pagosPendientes = Pago::where('estado', EstadoPago::PENDIENTE)->count();
        $pagosVencidos = Pago::where('estado', EstadoPago::VENCIDO)->count();
        $pagosAnulados = Pago::where('estado', EstadoPago::ANULADO)->count();

        $montoPagado = (float) Pago::where('estado', EstadoPago::PAGADO)->sum('monto');
        $montoPendiente = (float) Pago::where('estado', EstadoPago::PENDIENTE)->sum('monto');
        $montoVencido = (float) Pago::where('estado', EstadoPago::VENCIDO)->sum('monto');

        return [
            'cantidad' => [
                'total' => $totalPagos,
                'pagados' => $pagosPagados,
                'pendientes' => $pagosPendientes,
                'vencidos' => $pagosVencidos,
                'anulados' => $pagosAnulados,
            ],
            'montos' => [
                'pagado' => $montoPagado,
                'pendiente' => $montoPendiente,
                'vencido' => $montoVencido,
                'total' => $montoPagado + $montoPendiente + $montoVencido,
            ],
            'porcentajes' => [
                'cumplimiento' => $totalPagos > 0 ? round(($pagosPagados / $totalPagos) * 100, 2) : 0,
                'morosidad' => $totalPagos > 0 ? round(($pagosVencidos / $totalPagos) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Verifica la consistencia de un cronograma.
     * Valida que la suma de los montos de las cuotas coincida con el monto total.
     *
     * @param int $cronogramaId
     * @return array ['consistente' => bool, 'diferencia' => float, 'monto_total' => float, 'suma_cuotas' => float]
     */
    public function verificarConsistenciaCronograma(int $cronogramaId): array
    {
        $cronograma = Cronograma::with('pagos')->findOrFail($cronogramaId);

        $montoTotal = (float) $cronograma->monto_total;
        $sumaCuotas = (float) $cronograma->pagos()->sum('monto');
        $diferencia = abs($montoTotal - $sumaCuotas);

        // Tolerancia de 0.01 para diferencias de redondeo
        $consistente = $diferencia < 0.01;

        return [
            'consistente' => $consistente,
            'diferencia' => round($diferencia, 2),
            'monto_total' => $montoTotal,
            'suma_cuotas' => $sumaCuotas,
            'num_cuotas_esperadas' => $cronograma->num_cuotas,
            'num_cuotas_reales' => $cronograma->pagos()->count(),
        ];
    }
}
