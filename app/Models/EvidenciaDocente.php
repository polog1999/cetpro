<?php

namespace App\Models;

use App\Enums\TipoEvidencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenciaDocente extends Model
{
    protected $table = 'evidencias_docentes';

    protected $fillable = [
        'docente_id',
        'horario_id',
        'tipo_documento',
        'archivo_path',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'tipo_documento' => TipoEvidencia::class
    ];  

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class, 'horario_id', 'id_horario');
    }
}