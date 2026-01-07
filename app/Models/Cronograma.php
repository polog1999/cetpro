<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Usa comparación de strings ya que el estado viene de Oracle.
     */
    public function tieneDeuda(): bool
    {
        return $this->pagos()
            ->whereRaw("LOWER(estado) LIKE '%vencido%'")
            ->exists();
    }

    /**
     * Obtiene el total pagado hasta el momento.
     */
    public function totalPagado(): float
    {
        return (float) $this->pagos()
            ->whereRaw("LOWER(estado) LIKE '%cancelado%'")
            ->sum('monto');
    }

    /**
     * Obtiene el total pendiente de pago.
     */
    public function totalPendiente(): float
    {
        return (float) $this->pagos()
            ->whereRaw("LOWER(estado) LIKE '%pendiente%'")
            ->sum('monto');
    }

    /**
     * Obtiene el número de cuotas pagadas.
     */
    public function cuotasPagadas(): int
    {
        return $this->pagos()
            ->whereRaw("LOWER(estado) LIKE '%cancelado%'")
            ->count();
    }

    /**
     * Obtiene el número de cuotas vencidas.
     */
    public function cuotasVencidas(): int
    {
        return $this->pagos()
            ->whereRaw("LOWER(estado) LIKE '%vencido%'")
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
     * Actualiza el estado de vencimiento - ya no aplica porque el estado viene de Oracle.
     * 
     * @return int Número de cuotas actualizadas (siempre 0 ahora)
     */
    public function actualizarEstadosVencidos(): int
    {
        // El estado se gestiona desde Oracle, no localmente
        return 0;
    }

    /**
     * Obtiene la próxima cuota a pagar (pendiente o vencida más antigua).
     */
    public function proximaCuota(): ?Pago
    {
        return $this->pagos()
            ->where(function ($query) {
                $query->whereRaw("LOWER(estado) LIKE '%pendiente%'")
                      ->orWhereRaw("LOWER(estado) LIKE '%vencido%'");
            })
            ->orderBy('nro_cuota')
            ->first();
    }

    /**
     * Obtiene todas las cuotas vencidas.
     */
    public function cuotasVencidasDetalle()
    {
        return $this->pagos()
            ->whereRaw("LOWER(estado) LIKE '%vencido%'")
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
            'cuotas_pendientes' => $this->pagos()->whereRaw("LOWER(estado) LIKE '%pendiente%'")->count(),
            'cuotas_vencidas' => $this->cuotasVencidas(),
            'porcentaje_cumplimiento' => $this->porcentajeCumplimiento(),
            'esta_completo' => $this->estaCompletamentePagado(),
            'tiene_deuda' => $this->tieneDeuda(),
            'tiene_vencidas' => $this->tieneCuotasVencidas(),
        ];
    }
}
