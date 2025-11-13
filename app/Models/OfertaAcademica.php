<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\TipoOfertaAcademica;

class OfertaAcademica extends Model
{
    use HasFactory;

    protected $table = 'oferta_academica';   // o 'ofertas_academicas' según tu migración
    protected $primaryKey = 'id_oferta';

    protected $fillable = [
        'tipo_oferta',      // 'PROG_ESTUDIO', 'PROG_CONTINUA', 'CURSO_LIBRE'
        'id_programa',
        'id_curso',
        'id_rubro',
    ];

    protected $casts = [
        'tipo_oferta' => TipoOfertaAcademica::class,
    ];

    public function programa()
    {
        return $this->belongsTo(Programa::class, 'id_programa', 'id_programa');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }

    public function rubro()
    {
        return $this->belongsTo(Rubro::class, 'id_rubro', 'id_rubro');
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'oferta_academica_id', 'id_oferta');
    }
}
    