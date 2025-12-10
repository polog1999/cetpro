<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\Estudiante;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Apoderado extends Model
{
    use HasFactory;
    protected $fillable = [
        'tipo_documento',
        'nro_documento',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
    ];

    public function estudiantes() : HasMany
    {
        return $this->hasMany(Estudiante::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellido_paterno} {$this->apellido_materno}");
    }
}
