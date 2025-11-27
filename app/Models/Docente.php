<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Models\Horario;




class Docente extends Model
{
    protected $fillable = [
        'tipo_documento',
        'nro_documento',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
       
    ];

    
  public function horarios(): HasMany
{
    // FK en horarios = id_docente
    // PK en docentes = id
    return $this->hasMany(Horario::class, 'id_docente', 'id');
}
    
    // Accessor opcional para mostrar nombre completo en selects/listas
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellido_paterno} {$this->apellido_materno}");
    }
    
}
