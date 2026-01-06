<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\EstadoPago;

class Cronograma extends Model
{
    use HasFactory;

    protected $fillable = [
        'matricula_id',
        'num_cuotas',
        'monto_total',
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
    ];

    /**
     * Cronograma pertenece a una matrícula.
     */
    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    /**
     * Cronograma tiene muchos pagos (cuotas).
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    /**
     * Verifica si el cronograma tiene deudas (pagos vencidos).
     * Solo se considera deuda cuando el estado del pago es VENCIDO.
     */
    public function tieneDeuda(): bool
    {
        return $this->pagos()
            ->where('estado', EstadoPago::VENCIDO)
            ->exists();
    }

    /**
     * Obtiene el total pagado hasta el momento.
     */
    public function totalPagado(): float
    {
        return (float) $this->pagos()
            ->where('estado', EstadoPago::PAGADO)
            ->sum('monto');
    }

    /**
     * Obtiene el total pendiente de pago.
     */
    public function totalPendiente(): float
    {
        return (float) $this->pagos()
            ->where('estado', EstadoPago::PENDIENTE)
            ->sum('monto');
    }

    /**
     * Obtiene el número de cuotas pagadas.
     */
    public function cuotasPagadas(): int
    {
        return $this->pagos()
            ->where('estado', EstadoPago::PAGADO)
            ->count();
    }

    /**
     * Obtiene el número de cuotas vencidas.
     */
    public function cuotasVencidas(): int
    {
        return $this->pagos()
            ->where('estado', EstadoPago::VENCIDO)
            ->count();
    }

    /**
     * Obtiene el porcentaje de cumplimiento de pago.
     */
    public function porcentajeCumplimiento(): float
    {
        if ($this->num_cuotas == 0) {
            return 0;
        }

        return round(($this->cuotasPagadas() / $this->num_cuotas) * 100, 2);
    }

    /**
     * Verifica si el cronograma está completamente pagado.
     */
    public function estaCompletamentePagado(): bool
    {
        return $this->cuotasPagadas() === $this->num_cuotas;
    }

    /**
     * Actualiza el estado de vencimiento de todas las cuotas del cronograma.
     * Cambia cuotas PENDIENTE a VENCIDO si ya pasó su fecha de vencimiento.
     * 
     * @return int Número de cuotas actualizadas
     */
    public function actualizarEstadosVencidos(): int
    {
        return $this->pagos()
            ->where('estado', EstadoPago::PENDIENTE)
            ->where('fecha_vencimiento', '<', now()->startOfDay())
            ->update(['estado' => EstadoPago::VENCIDO]);
    }

    /**
     * Obtiene la próxima cuota a pagar (pendiente o vencida más antigua).
     */
    public function proximaCuota(): ?Pago
    {
        return $this->pagos()
            ->whereIn('estado', [EstadoPago::PENDIENTE, EstadoPago::VENCIDO])
            ->orderBy('nro_cuota')
            ->first();
    }

    /**
     * Obtiene todas las cuotas vencidas.
     */
    public function cuotasVencidasDetalle()
    {
        return $this->pagos()
            ->where('estado', EstadoPago::VENCIDO)
            ->orderBy('fecha_vencimiento')
            ->get();
    }

    /**
     * Verifica si hay cuotas vencidas.
     */
    public function tieneCuotasVencidas(): bool
    {
        return $this->cuotasVencidas() > 0;
    }

    /**
     * Obtiene un resumen completo del cronograma.
     */
    public function resumen(): array
    {
        return [
            'num_cuotas' => $this->num_cuotas,
            'monto_total' => $this->monto_total,
            'total_pagado' => $this->totalPagado(),
            'total_pendiente' => $this->totalPendiente(),
            'cuotas_pagadas' => $this->cuotasPagadas(),
            'cuotas_pendientes' => $this->pagos()->where('estado', EstadoPago::PENDIENTE)->count(),
            'cuotas_vencidas' => $this->cuotasVencidas(),
            'porcentaje_cumplimiento' => $this->porcentajeCumplimiento(),
            'esta_completo' => $this->estaCompletamentePagado(),
            'tiene_deuda' => $this->tieneDeuda(),
            'tiene_vencidas' => $this->tieneCuotasVencidas(),
        ];
    }
}

