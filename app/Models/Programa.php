<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    use HasFactory;

    protected $table = 'programas';
    protected $primaryKey = 'id_programa';

    protected $fillable = [
        'modalidad',        // ENUM en la BD
        'turno',            // ENUM en la BD
        'nombre_programa',
        'duracion',
        'dias',
        'horario',
        'num_componentes',
        'docente_id',
        'id_rubro',
    ];

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function rubro()
    {
        return $this->belongsTo(Rubro::class, 'id_rubro', 'id_rubro');
    }

    public function cursos()
    {
        return $this->hasMany(Curso::class, 'id_programa', 'id_programa');
    }

    public function ofertasAcademicas()
    {
        return $this->hasMany(OfertaAcademica::class, 'id_programa', 'id_programa');
    }
}
