<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Enums\Turno;
use App\Enums\Modalidad;

class Horario extends Model
{
    use HasFactory;

    // Nueva tabla y nueva PK
    protected $table = 'horarios';
    protected $primaryKey = 'id_horario';

    protected $fillable = [
        'id_programa',
        'turno',
        'dias',
        'hora_inicio',
        'hora_fin',
        'modalidad',
        'id_docente',
        'aula',
        'vacantes',
        'activo',
    ];

    protected $casts = [
        'turno'     => Turno::class,
        'modalidad' => Modalidad::class,
        'dias'      => 'array',
        'activo'    => 'boolean',
    ];

    public function programa()
    {
        return $this->belongsTo(Programa::class, 'id_programa', 'id_programa');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id');
    }

    public function matriculas(): HasMany
{
    return $this->hasMany(Matricula::class, 'horario_id', 'id_horario');
}
}
