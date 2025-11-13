<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

    protected $table = 'cursos';
    protected $primaryKey = 'id_curso';

    protected $fillable = [
        'nombre_curso',
        'duracion',
        'fecha_inicio',
        'fecha_termino',
        'aula',
        'id_programa',
    ];

    public function programa()
    {
        return $this->belongsTo(Programa::class, 'id_programa', 'id_programa');
    }

    public function ofertasAcademicas()
    {
        return $this->hasMany(OfertaAcademica::class, 'id_curso', 'id_curso');
    }
}
