<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;   // << implementarlo
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Importar HasFactory

class Usuario extends Authenticatable implements FilamentUser, HasName
{
    use Notifiable, HasFactory, HasApiTokens;

    protected $table = 'usuarios';

    protected $fillable = [
        'empleado_id',
        'role_id',      // << Nuevo campo
        'usuario',      // campo “username”
        'password',
        'remember_token',
        'activo',       // << Nuevo campo
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => filled($value)
                ? (Hash::needsRehash($value) ? Hash::make($value) : $value)
                : $value
        );
    }
    protected $casts = [
        'password' => 'hashed',
        'activo' => 'boolean',  // << Casteo a booleano
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    /**
     * Relación con Role
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Verificar si el usuario puede acceder a un recurso
     */
    public function canAccessResource(string $recurso): bool
    {
        return $this->role?->hasPermission($recurso) ?? false;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Si tiene rol asignado, verificar que tenga permisos
        if ($this->role) {
            return true; // Cualquier rol puede acceder al panel (los permisos se verifican en cada recurso)
        }

        return false;
    }

    // Requerido por HasName para mostrar el nombre en el topbar de Filament
    public function getFilamentName(): string
    {
        if ($this->empleado) {
            return trim(
                $this->empleado->nombre . ' ' . 
                $this->empleado->apellido_paterno . ' ' . 
                ($this->empleado->apellido_materno ?? '')
            );
        }
        
        return $this->usuario ?? 'Usuario';
    }
}
