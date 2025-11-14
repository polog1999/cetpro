<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rubro extends Model
{
    use HasFactory;

    protected $table = 'rubros';
    protected $primaryKey = 'id_rubro';

    protected $fillable = [
        'nombre_rubro',
        'costo_mensual',
        'num_resolucion',
        'fecha_registro',
        'fecha_inicio_vigencia',
        'fecha_fin_vigencia',
    ];

    // Relación con Programa
    public function programas()
    {
        return $this->hasMany(Programa::class, 'id_rubro', 'id_rubro');
    }

    // Relación con secciones
    public function Secciones()
    {
        return $this->hasMany(Seccion::class, 'id_rubro', 'id_rubro');
    }
    public function docente()
    {
        return $this->belongsTo(Docente::class, 'docente_id', 'id');  // 👈 CAMBIO
    }
}
