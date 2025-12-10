<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use App\Enums\EstadoPago;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'cronograma_id',
        'usuario_id',
        'nro_cuota',
        'codigo',
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
        'estado'            => EstadoPago::class,
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
        return $this->estado === EstadoPago::PENDIENTE 
            && $this->fecha_vencimiento < now()->startOfDay();
    }

    /**
     * Verifica si el pago puede ser procesado.
     */
    public function puedeSerPagado(): bool
    {
        return $this->estado->puedeSerPagado();
    }

    /**
     * Verifica si el estado es final (no puede cambiar).
     */
    public function estadoEsFinal(): bool
    {
        return $this->estado->esFinal();
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
                'estado' => "No se puede pagar una cuota en estado: {$this->estado->getLabel()}",
            ]);
        }

        // Actualizar el pago
        $this->update([
            'estado' => EstadoPago::PAGADO,
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
        if ($this->estado === EstadoPago::ANULADO) {
            throw ValidationException::withMessages([
                'estado' => 'Esta cuota ya está anulada.',
            ]);
        }

        $this->update([
            'estado' => EstadoPago::ANULADO,
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
        if ($this->estado !== EstadoPago::PAGADO) {
            throw ValidationException::withMessages([
                'estado' => 'Solo se pueden revertir pagos que estén en estado PAGADO.',
            ]);
        }

        // Cambiar a pendiente o vencido según la fecha
        $nuevoEstado = $this->fecha_vencimiento < now()->startOfDay() 
            ? EstadoPago::VENCIDO 
            : EstadoPago::PENDIENTE;

        $this->update([
            'estado' => $nuevoEstado,
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
        if ($this->estaVencido()) {
            $this->update(['estado' => EstadoPago::VENCIDO]);
            return true;
        }

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
     * Generación automática del código del pago y el número de cuota.
     * Formato: {dni_alumno}-{id_horario}-{numero Pago}
     */
    protected static function booted(): void
    {
        static::creating(function (Pago $pago) {
            // Asegurarnos de tener el cronograma cargado
            $cronograma = $pago->cronograma ?? Cronograma::findOrFail($pago->cronograma_id);
            $matricula  = $cronograma->matricula;
            
            // Obtener DNI del alumno
            $dniAlumno = $matricula->estudiante->nro_documento ?? 'SIN-DNI';
            
            // Obtener ID de horario (puede ser null si es curso libre)
            $idHorario = $matricula->horario_id ?? $matricula->id_curso ?? 'SIN-HORARIO';
            
            // Contar pagos existentes para ese cronograma
            $conteoPagos = Pago::where('cronograma_id', $pago->cronograma_id)->count();
            $numeroPago  = str_pad($conteoPagos + 1, 2, '0', STR_PAD_LEFT); // 01, 02, 03...

            // Si no viene seteado, asignamos correlativo de cuota
            if (is_null($pago->nro_cuota)) {
                $pago->nro_cuota = $conteoPagos + 1;
            }

            // Si no viene un código ya definido, lo generamos
            // Formato: {dni_alumno}-{id_horario}-{numero Pago}
            if (empty($pago->codigo)) {
                $pago->codigo = "{$dniAlumno}-{$idHorario}-{$numeroPago}";
            }
        });

        // Actualizar estado a vencido automáticamente al cargar
        static::retrieved(function (Pago $pago) {
            if ($pago->estaVencido() && $pago->estado === EstadoPago::PENDIENTE) {
                $pago->updateQuietly(['estado' => EstadoPago::VENCIDO]);
            }
        });
    }
}
