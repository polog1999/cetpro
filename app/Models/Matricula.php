<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function cronograma(): HasOne
    {
        return $this->hasOne(Cronograma::class);
    }

    // Genera y guarda el cronograma de pagos de esta matrícula.
    public function generarCronograma(): ?Cronograma
    {
        $duracion     = null;
        $especialidad = null;

        // 1) CURSO LIBRE → duración del curso
        if ($this->tipo_matricula === TipoMatricula::CURSO_LIBRE) {
            $curso = $this->curso;

            if (! $curso) {
                return null; // no hay curso asociado
            }

            $duracion     = $curso->duracion;                    // p.ej. en meses
            $especialidad = $curso->programa?->especialidad;     // programa del curso → especialidad
        }

        // 2) PROG_ESTUDIO o FORM_CONTINUA → duración del programa
        if (in_array($this->tipo_matricula, [TipoMatricula::PROG_ESTUDIO, TipoMatricula::FORM_CONTINUA], true)) {
            $programa = $this->seccion?->programa;

            if (! $programa) {
                return null;
            }

            $duracion     = $programa->duracion;                 // p.ej. en meses
            $especialidad = $programa->especialidad;
        }

        if (! $duracion || ! $especialidad) {
            return null;
        }

        // num_cuotas = duración
        $numCuotas  = (int) $duracion;

        // monto_total = costo_mensual de la especialidad * duración
        $montoTotal = $numCuotas * $especialidad->costo_mensual;

        return $this->cronograma()->create([
            'num_cuotas'  => $numCuotas,
            'monto_total' => $montoTotal,
        ]);
    }

    protected static function booted()
    {
        static::created(function (Matricula $matricula) {
            $matricula->generarCronograma();
        });
    }
    
}
