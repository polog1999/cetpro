<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para unidades dentro de módulos/cursos.
 * 
 * Jerarquía: Programa → Módulo (curso) → Unidades
 * Solo se utiliza para programas de tipo PROGRAMA_ESTUDIO.
 */
class Unidad extends Model
{
    use HasFactory;

    protected $table = 'unidades';
    protected $primaryKey = 'id_unidad';

    protected $fillable = [
        'id_curso',
        'nombre_unidad',
        'duracion',
        'orden',
        'descripcion',
    ];

    protected $casts = [
        'duracion' => 'integer',
        'orden' => 'integer',
    ];

    /**
     * Módulo/Curso al que pertenece esta unidad.
     */
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }

    /**
     * Alias para mayor claridad semántica: módulo padre.
     */
    public function modulo(): BelongsTo
    {
        return $this->curso();
    }

    /**
     * Obtiene el programa a través del curso/módulo.
     */
    public function getProgramaAttribute()
    {
        return $this->curso?->programa;
    }

    /**
     * Scope para ordenar por el campo 'orden'.
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden');
    }
}
