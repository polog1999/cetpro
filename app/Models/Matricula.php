<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

use App\Models\Estudiante;
use App\Models\Seccion;
use App\Models\Curso;
use App\Enums\EstadoMatricula;
use App\Enums\TipoMatricula;

class Matricula extends Model
{
    use HasFactory;

    protected $table = 'matriculas';

    protected $fillable = [
        'codigo_inscripcion',
        'estudiante_id',
        'seccion_id',
        'estado',
        'tipo_matricula',
        'id_curso',
    ];

    protected $casts = [
        'estado'        => EstadoMatricula::class,
        'tipo_matricula'=> TipoMatricula::class,
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class, 'seccion_id', 'id_seccion');
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }
}
