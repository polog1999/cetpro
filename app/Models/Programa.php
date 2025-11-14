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
        
        'nombre_programa',
        'duracion',
        'num_componentes',
        'id_rubro',
    ];

    // ❌ Ya no hay docente_id en esta tabla, así que esta relación sobra
    // public function docente()
    // {
    //     return $this->belongsTo(Docente::class, 'docente_id', 'id');
    // }

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
