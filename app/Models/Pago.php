<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'cronograma_id',
        'usuario_id',
        'nro_cuota',
        'monto',
        'estado',
        'fecha_vencimiento',
        'metodo_pago',
        'fecha_pago',
        'evidencia_path',
        'num_liquidacion',     
        'fecha_liquidacion',   
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_pago'        => 'date',
        'monto'             => 'decimal:2',
        'fecha_liquidacion' => 'date',
    ];

    /**
     * Cada pago pertenece a un cronograma.
     */
    public function cronograma(): BelongsTo
    {
        return $this->belongsTo(Cronograma::class);
    }

    /**
     * Cada pago fue registrado por un usuario.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Accesor conveniente para obtener la matrícula del pago:
     * $pago->matricula -> instancia de Matricula o null.
     */
    public function getMatriculaAttribute()
    {
        return $this->cronograma?->matricula;
    }

    /**
     * Verifica si el pago está vencido.
     */
    public function estaVencido(): bool
    {
        // Verifica si la fecha de vencimiento ya pasó
        return $this->fecha_vencimiento < now()->startOfDay();
    }

    /**
     * Verifica si el pago puede ser procesado.
     */
    public function puedeSerPagado(): bool
    {
        // Puede ser pagado si el estado contiene 'Pendiente' (desde Oracle)
        return str_contains(strtolower($this->estado ?? ''), 'pendiente');
    }

    /**
     * Verifica si el estado es final (no puede cambiar).
     */
    public function estadoEsFinal(): bool
    {
        // Estado final si está cancelado (pagado en Oracle) o anulado
        $estado = strtolower($this->estado ?? '');
        return str_contains($estado, 'cancelado') || str_contains($estado, 'anulado');
    }

    /**
     * Registra el pago de esta cuota.
     *
     * @param string $metodoPago
     * @param string|null $evidenciaPath
     * @param int|null $usuarioId
     * @return void
     * @throws ValidationException
     */
    public function registrarPago(string $metodoPago, ?string $evidenciaPath = null, ?int $usuarioId = null): void
    {
        // Validar que se pueda pagar
        if (!$this->puedeSerPagado()) {
            throw ValidationException::withMessages([
                'estado' => "No se puede pagar una cuota con estado: {$this->estado}",
            ]);
        }

        // Actualizar el pago (el estado real se sincroniza desde Oracle)
        $this->update([
            'fecha_pago' => now(),
            'metodo_pago' => $metodoPago,
            'evidencia_path' => $evidenciaPath,
            'usuario_id' => $usuarioId ?? auth()->id(),
        ]);

        // Actualizar el estado de la matrícula según el cronograma
        $this->cronograma->matricula->actualizarEstadoSegunCronograma();
    }

    /**
     * Anula el pago.
     *
     * @return void
     * @throws ValidationException
     */
    public function anular(): void
    {
        // No se puede anular un pago ya anulado
        if (str_contains(strtolower($this->estado ?? ''), 'anulado')) {
            throw ValidationException::withMessages([
                'estado' => 'Esta cuota ya está anulada.',
            ]);
        }

        $this->update([
            'estado' => 'Anulado',
        ]);
    }

    /**
     * Revierte un pago (solo si tiene permisos especiales).
     *
     * @param string $motivo
     * @return void
     * @throws ValidationException
     */
    public function revertirPago(string $motivo): void
    {
        $estadoLower = strtolower($this->estado ?? '');
        if (!str_contains($estadoLower, 'cancelado')) {
            throw ValidationException::withMessages([
                'estado' => 'Solo se pueden revertir pagos que estén en estado CANCELADO.',
            ]);
        }

        // Cambiar a Pendiente (el estado real se sincronizará desde Oracle)
        $this->update([
            'estado' => 'Pendiente',
            'fecha_pago' => null,
            'metodo_pago' => null,
            'evidencia_path' => null,
        ]);

        // Actualizar el estado de la matrícula según el cronograma
        $this->cronograma->matricula->actualizarEstadoSegunCronograma();
    }

    /**
     * Actualiza el estado a vencido si corresponde.
     *
     * @return bool True si se actualizó
     */
    public function actualizarSiVencido(): bool
    {
        // Ya no actualizamos localmente - el estado viene de Oracle
        return false;
    }

    /**
     * Obtiene los días de retraso (solo si está vencido o es pendiente pasada la fecha).
     */
    public function diasRetraso(): int
    {
        if ($this->estado === EstadoPago::PAGADO) {
            return 0;
        }

        if ($this->fecha_vencimiento >= now()->startOfDay()) {
            return 0;
        }

        return now()->startOfDay()->diffInDays($this->fecha_vencimiento);
    }

    /**
     * Generación automática del número de cuota.
     */
    protected static function booted(): void
    {
        static::creating(function (Pago $pago) {
            // Asegurarnos de tener el cronograma cargado
            $cronograma = $pago->cronograma ?? Cronograma::findOrFail($pago->cronograma_id);
            
            // Contar pagos existentes para ese cronograma
            $conteoPagos = Pago::where('cronograma_id', $pago->cronograma_id)->count();

            // Si no viene seteado, asignamos correlativo de cuota
            if (is_null($pago->nro_cuota)) {
                $pago->nro_cuota = $conteoPagos + 1;
            }
        });

        // Ya no actualizamos el estado al cargar - viene de Oracle
        static::retrieved(function (Pago $pago) {
            // El estado se gestiona desde Oracle, no localmente
        });
    }
}
