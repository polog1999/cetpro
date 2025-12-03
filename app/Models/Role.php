<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'descripcion',
        'es_admin',
    ];

    protected $casts = [
        'es_admin' => 'boolean',
    ];

    /**
     * Relación muchos a muchos con Permiso
     */
    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'role_permiso', 'role_id', 'permiso_id')
            ->withTimestamps();
    }

    /**
     * Relación con usuarios
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'role_id');
    }

    /**
     * Verificar si el rol tiene permiso para acceder a un recurso específico
     */
    public function hasPermission(string $recurso): bool
    {
        // Si es admin, tiene acceso a todo
        if ($this->es_admin) {
            return true;
        }

        // Verificar si tiene el permiso específico
        return $this->permisos()->where('recurso', $recurso)->exists();
    }

    /**
     * Obtener array de recursos permitidos
     */
    public function getRecursosPermitidos(): array
    {
        if ($this->es_admin) {
            return ['*']; // Todos los recursos
        }

        return $this->permisos()->pluck('recurso')->toArray();
    }
}
