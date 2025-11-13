<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\Estudiante;
use App\Models\OfertaAcademica;
use App\Enums\EstadoMatricula;
use Illuminate\Database\Eloquent\Model;

class Matricula extends Model
{
    use HasFactory;

    protected $table = 'matriculas';

    protected $fillable = [
        'estudiante_id',
        'oferta_academica_id',
        'estado',
    ];

    protected $casts = [
        'estado' => EstadoMatricula::class,
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function ofertaAcademica(): BelongsTo
    {
        return $this->belongsTo(
            OfertaAcademica::class,
            'oferta_academica_id',
            'id_oferta'
        );
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Matricula $matricula) {

            // 1. Obtenemos la Oferta Académica
            $oferta = OfertaAcademica::find($matricula->oferta_academica_id);
            $codigoOferta = $oferta
                ? 'OF-' . str_pad($oferta->id_oferta, 4, '0', STR_PAD_LEFT)
                : 'SIN-OFERTA';

            // 2. Obtenemos el Estudiante
            $estudiante = Estudiante::find($matricula->estudiante_id);
            $dni = $estudiante->nro_documento ?? 'SIN-DNI';

            // 3. Creamos el código de la matrícula
            // Formato ejemplo: OF-0001-12345678
            $matricula->codigo = "{$codigoOferta}-{$dni}";
        });
    }
}
