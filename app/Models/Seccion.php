<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\TipoOfertaAcademica;
use App\Enums\Turno;
use App\Enums\Modalidad; 

class Seccion extends Model
{
    use HasFactory;

    // el nombre de la tabla NUEVA
    protected $table = 'seccion'; // o 'secciones', según la migrate
    protected $primaryKey = 'id_seccion'; // mientras no cambies el nombre de la PK

    protected $fillable = [
        'seccion',
        'id_programa',
        'id_curso',
        'id_rubro',
        'modalidad',
        'turno',
        'dias',
        'horario',
        'docente_id',
    ];

    protected $casts = [
        'tipo_oferta' => TipoOfertaAcademica::class,
        'modalidad'   => Modalidad::class,
        'turno'       => Turno::class,
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

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'docente_id', 'id');
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'seccion_id', 'id_seccion');
        // si luego cambias este campo en la BD, aquí también lo ajustas
    }
}
