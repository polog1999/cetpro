<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\TipoEvaluacion;

class Nota extends Model
{
    use HasFactory;

    protected $fillable = [
        'matricula_id',
        'curso_id',
        'docente_id',
        'tipo_evaluacion',
        'periodo',
        'nota',
        'nota_letra',
        'observaciones',
        'fecha_evaluacion',
    ];

    protected $casts = [
        'tipo_evaluacion' => TipoEvaluacion::class,
        'fecha_evaluacion' => 'date',
        'nota' => 'decimal:2',
    ];

    /**
     * Relación con Matricula
     */
    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    /**
     * Relación con Curso
     */
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_id', 'id_curso');
    }

    /**
     * Relación con Docente
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    /**
     * Relación con Estudiante a través de Matricula
     */
    public function estudiante()
    {
        return $this->matricula->estudiante();
    }

    /**
     * Relación con Horario a través de Matricula
     */
    public function horario()
    {
        return $this->matricula->horario();
    }

    /**
     * Scope para filtrar notas por profesor
     */
    public function scopePorProfesor($query, int $docenteId)
    {
        return $query->where('docente_id', $docenteId);
    }

    /**
     * Scope para filtrar notas por estudiante
     */
    public function scopePorEstudiante($query, int $estudianteId)
    {
        return $query->whereHas('matricula', function ($q) use ($estudianteId) {
            $q->where('estudiante_id', $estudianteId);
        });
    }

    /**
     * Scope para filtrar notas por curso
     */
    public function scopePorCurso($query, int $cursoId)
    {
        return $query->where('curso_id', $cursoId);
    }

    /**
     * Accessor para nota formateada
     */
    public function getNotaFormateadaAttribute(): string
    {
        return number_format($this->nota, 2);
    }

    /**
     * Verificar si la nota es aprobatoria (>= 11)
     */
    public function esAprobatoria(): bool
    {
        return $this->nota >= 11;
    }
}
