<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Enums\TipoPrograma;
use App\Models\Especialidad;
use App\Models\Curso;
use App\Models\Horario;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Programa extends Model
{
    use HasFactory;

    protected $table = 'programas';
    protected $primaryKey = 'id_programa';

    protected $fillable = [
        'nombre_programa',
        'duracion',
        'num_cursos',
        'id_especialidad',   // 👈 ya NO id_rubro
        'tipo_programa',
    ];

    protected $casts = [
        'tipo_programa' => TipoPrograma::class,
    ];

    public function especialidad()
    {
        // FK en programas: id_especialidad
        // PK en especialidades: id_especialidad
        return $this->belongsTo(Especialidad::class, 'id_especialidad', 'id_especialidad');
    }

    public function cursos()
    {
        return $this->hasMany(Curso::class, 'id_programa', 'id_programa');
    }

    public function horarios(): HasMany
{
    return $this->hasMany(Horario::class, 'id_programa', 'id_programa');
}
    
}
