<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnidadDidacticaUgel extends Model
{
    use HasFactory;

    protected $table = 'unidades_didacticas_ugel';

    protected $fillable = [
        'nombre',
        'programa_id',
        'curso_id',
        'creditos',
        'horas',
        'capacidad',
        'orden',
        'es_efsrt',
    ];

    protected $casts = [
        'es_efsrt' => 'boolean',
    ];

    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class, 'programa_id', 'id_programa');
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_id', 'id_curso');
    }
     public function scopeOrdenado($query)
    {
        return $query->orderBy('orden');
    }
}