<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Models\Seccion;
use App\Models\Modulo;


class Docente extends Model
{
    protected $fillable = [
        'tipo_documento',
        'nro_documento',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
       
    ];

    
  public function programas(): HasMany
    {
        // FK en programas = docente_id
        // PK en docentes = id
        return $this->hasMany(Programa::class, 'docente_id', 'id');
    }
    
    // Accessor opcional para mostrar nombre completo en selects/listas
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellido_paterno} {$this->apellido_materno}");
    }
    
}
