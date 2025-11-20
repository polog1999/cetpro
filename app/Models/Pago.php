<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Enums\EstadoPago;

class Pago extends Model
{
    use HasFactory;

    // Tabla "pagos" por convención, no es necesario $table
    // protected $table = 'pagos';

    protected $fillable = [
        'cronograma_id',
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
     * Accesor conveniente para obtener la matrícula del pago:
     * $pago->matricula -> instancia de Matricula o null.
     */
    public function getMatriculaAttribute()
    {
        return $this->cronograma?->matricula;
    }

    /**
     * Genera automáticamente el código del pago y el número de cuota.
     */
    protected static function booted(): void
    {
        static::creating(function (Pago $pago) {
            // Asegurarnos de tener el cronograma cargado
            $cronograma = $pago->cronograma ?? Cronograma::findOrFail($pago->cronograma_id);
            $matricula  = $cronograma->matricula;

            // Ajusta esto según tu modelo Matricula:
            // si tienes un accessor getCodigoAttribute(), usa $matricula->codigo
            // si no, usa directamente codigo_inscripcion.
            $codigoMatricula = $matricula->codigo ?? $matricula->codigo_inscripcion;

            // Contar pagos existentes para ese cronograma
            $conteoPagos = Pago::where('cronograma_id', $pago->cronograma_id)->count();
            $numeroPago  = str_pad($conteoPagos + 1, 2, '0', STR_PAD_LEFT); // 01, 02, 03...

            // Si no viene seteado, asignamos correlativo de cuota
            if (is_null($pago->nro_cuota)) {
                $pago->nro_cuota = $conteoPagos + 1;
            }

            // Si no viene un código ya definido, lo generamos
            if (empty($pago->codigo)) {
                $pago->codigo = "{$codigoMatricula}-{$numeroPago}";
            }
        });
    }
}
