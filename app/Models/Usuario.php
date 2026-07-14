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
        'docente_id',   // << Vinculación con docentes
        'estudiante_id', // << Vinculación con estudiantes (portal)
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
            set: fn($value) => filled($value)
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
     * Relación con Docente
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    /**
     * Relación con Estudiante (para portal de alumnos)
     */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    /**
     * Verificar si el usuario es alumno (estudiante)
     */
    public function esAlumno(): bool
    {
        return $this->role?->nombre === 'Alumno' || $this->estudiante_id !== null;
    }

    /**
     * Verificar si el usuario es profesor
     */
    public function esProfesor(): bool
    {
        return $this->role?->nombre === 'Profesor' || $this->docente_id !== null;
    }

    public function esDirectora(): bool
    {
        return $this->role?->nombre === 'Directora' || (is_null($this->directora_id)  && is_null($this->directora_id));
    }
    /**
     * Verificar si el usuario es administrador
     */
    public function esAdmin(): bool
    {
        return $this->role?->es_admin ?? false;
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
        // Los alumnos NO pueden acceder al panel de Filament
        if ($this->esAlumno()) {
            return false;
        }

        // Si tiene rol asignado (y no es alumno), puede acceder
        if ($this->role) {
            return true;
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

        if ($this->docente) {
            return trim(
                $this->docente->nombres . ' ' .
                    $this->docente->apellido_paterno . ' ' .
                    ($this->docente->apellido_materno ?? '')
            );
        }

        return $this->usuario ?? 'Usuario';
    }
}
