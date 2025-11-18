<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Especialidad extends Model
{
    use HasFactory;

    protected $table = 'especialidades';
    protected $primaryKey = 'id_especialidad';

    protected $fillable = [
        'nombre_especialidad',
        'costo_mensual',
        'num_resolucion',
        'fecha_registro',
        'fecha_inicio_vigencia',
        'fecha_fin_vigencia',
    ];

    public function programas()
    {
        return $this->hasMany(Programa::class, 'id_especialidad', 'id_especialidad');
    }
}
