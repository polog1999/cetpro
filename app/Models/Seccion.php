<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Enums\Turno;
use App\Enums\Modalidad;

class Seccion extends Model
{
    use HasFactory;

    // Nombre actual de la tabla
    protected $table = 'seccion'; // si tu tabla se llama 'secciones', cámbialo
    protected $primaryKey = 'id_seccion';

    protected $fillable = [
        'id_programa',
        'turno',
        'dias',
        'horario',
        'modalidad',
        'id_docente',
        'aula',
    ];

    protected $casts = [
        'turno'     => Turno::class,
        'modalidad' => Modalidad::class,
        'dias'         => 'array',
    ];

    public function programa()
    {
        return $this->belongsTo(Programa::class, 'id_programa', 'id_programa');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id');
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'seccion_id', 'id_seccion');
    }
}

