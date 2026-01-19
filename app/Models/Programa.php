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

    /**
     * Calcula la duración real del programa basada en la suma de las duraciones de sus cursos/módulos.
     * 
     * @return int Suma de las duraciones de todos los cursos en meses
     */
    public function getDuracionRealCursos(): int
    {
        return (int) $this->cursos()->sum('duracion');
    }

    /**
     * Verifica si el programa está completo y listo para matrículas.
     * 
     * Un programa está completo si:
     * 1. Tiene al menos un curso/módulo asociado
     * 2. La suma de las duraciones de sus cursos es igual o mayor a la duración del programa
     * 
     * @return bool True si el programa está completo
     */
    public function estaCompleto(): bool
    {
        // Debe tener al menos un curso
        $cantidadCursos = $this->cursos()->count();
        if ($cantidadCursos === 0) {
            return false;
        }

        // La suma de duraciones de cursos debe ser >= duración del programa
        $duracionPrograma = (int) $this->duracion;
        $duracionRealCursos = $this->getDuracionRealCursos();

        return $duracionRealCursos >= $duracionPrograma;
    }

    /**
     * Scope para obtener solo programas completos (listos para matrícula).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompletos($query)
    {
        return $query->whereHas('cursos')
            ->whereRaw('(SELECT COALESCE(SUM(duracion), 0) FROM cursos WHERE cursos.id_programa = programas.id_programa) >= programas.duracion');
    }
    
}
