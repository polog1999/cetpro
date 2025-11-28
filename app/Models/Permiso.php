<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    use HasFactory;

    protected $table = 'permisos';

    protected $fillable = [
        'recurso',
        'nombre',
        'grupo',
        'descripcion',
    ];

    /**
     * Relación muchos a muchos con Role
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permiso', 'permiso_id', 'role_id')
            ->withTimestamps();
    }
}
