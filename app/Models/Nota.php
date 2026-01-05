<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\CalificacionLetra;

/**
 * Modelo para notas finales de cursos/módulos.
 * 
 * Cada nota representa la calificación final de un estudiante
 * en un curso específico dentro de su matrícula.
 */
class Nota extends Model
{
    use HasFactory;

    protected $table = 'notas';

    protected $fillable = [
        'matricula_id',
        'curso_id',
        'docente_id',
        'nota_numerica',
        'nota_letra',
        'pdf_calificacion',
        'observaciones',
    ];

    protected $casts = [
        'nota_numerica' => 'decimal:2',
        'nota_letra' => CalificacionLetra::class,
    ];

    /**
     * Matrícula asociada a esta nota.
     */
    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    /**
     * Curso al que corresponde esta nota.
     */
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_id', 'id_curso');
    }

    /**
     * Docente que registró la nota.
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    /**
     * Obtiene el estudiante a través de la matrícula.
     */
    public function getEstudianteAttribute()
    {
        return $this->matricula?->estudiante;
    }

    /**
     * Scope para filtrar notas por profesor.
     */
    public function scopePorProfesor($query, int $docenteId)
    {
        return $query->where('docente_id', $docenteId);
    }

    /**
     * Scope para filtrar notas por estudiante.
     */
    public function scopePorEstudiante($query, int $estudianteId)
    {
        return $query->whereHas('matricula', function ($q) use ($estudianteId) {
            $q->where('estudiante_id', $estudianteId);
        });
    }

    /**
     * Verifica si la nota tiene calificación registrada.
     */
    public function tieneCalificacion(): bool
    {
        return $this->nota_numerica !== null || $this->nota_letra !== null;
    }

    /**
     * Verifica si la nota está aprobada (>= 11 o letra A/AD).
     */
    public function estaAprobada(): bool
    {
        if ($this->nota_numerica !== null) {
            return $this->nota_numerica >= 11;
        }

        if ($this->nota_letra !== null) {
            return in_array($this->nota_letra, [CalificacionLetra::AD, CalificacionLetra::A]);
        }

        return false;
    }
}
