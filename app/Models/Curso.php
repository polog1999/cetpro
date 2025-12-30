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
        'id_programa',
    ];

    public function programa()
    {
        return $this->belongsTo(Programa::class, 'id_programa', 'id_programa');
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'id_curso', 'id_curso');
    }

    /**
     * Relación con Notas
     */
    public function notas()
    {
        return $this->hasMany(Nota::class, 'curso_id', 'id_curso');
    }
}
