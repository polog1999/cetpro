<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

use App\Models\Apoderado;
use App\Models\Nota;
use App\Models\Matricula;
use App\Models\Usuario;
use App\Enums\EstadoCivil;
use App\Enums\TipoGenero;
use App\Enums\TipoDocumento;
use App\Enums\GradoInstruccion;
use App\Enums\Provincia;
use App\Enums\DistritoLima;
// Enums del Censo Escolar
use App\Enums\TipoDiscapacidad;
use App\Enums\SubtipoDiscapacidad;
use App\Enums\TipoProgramaReparacion;
use App\Enums\LenguaMaterna;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Estudiante extends Model
{
    use HasFactory;
    // protected $table = 'estudiantes';

    protected $fillable = [
        'tipo_documento',      // ENUM (TipoDocumento)
        'nro_documento',       // documento de identidad
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'genero',              // ENUM (TipoGenero) -> en el formulario lo etiquetas como "Sexo"
        'estado_civil',        // ENUM (EstadoCivil)
        'fecha_nacimiento',
        'telefono',
        'direccion',           // en el formulario lo puedes mostrar como "Domicilio"
        'email',               // correo electrónico
        'codigo_contribuyente', // Código Oracle (C0000001)
        'grado_instruccion',   // ENUM (GradoInstruccion)
        'provincia',           // ENUM (Provincia) - solo Lima
        'distrito',            // ENUM (DistritoLima)
        'apoderado_id',
        // Campos del Censo Escolar
        'tipo_discapacidad',       // ENUM (TipoDiscapacidad) - Tabla 205
        'subtipo_discapacidad',    // ENUM (SubtipoDiscapacidad) - Sub-tipos para AUDITIVA/VISUAL
        'tipo_programa_reparacion', // ENUM (TipoProgramaReparacion) - Tabla 206
        'lengua_materna',          // ENUM (LenguaMaterna) - Tabla 207
        'anio_egreso_ebr',         // INTEGER - Año de egreso EBR (Tabla 204)
    ];

    protected $casts = [
        'fecha_nacimiento'  => 'date',
        'genero'            => TipoGenero::class,
        'estado_civil'      => EstadoCivil::class,
        'grado_instruccion' => GradoInstruccion::class,
        'tipo_documento'    => TipoDocumento::class,
        'provincia'         => Provincia::class,
        'distrito'          => DistritoLima::class,
        // Casts para campos del Censo
        'tipo_discapacidad'       => TipoDiscapacidad::class,
        'subtipo_discapacidad'    => SubtipoDiscapacidad::class,
        'tipo_programa_reparacion' => TipoProgramaReparacion::class,
        'lengua_materna'          => LenguaMaterna::class,
        'anio_egreso_ebr'         => 'integer',
    ];

    // Para poder acceder a $estudiante->edad y $estudiante->edad_al_31_marzo
    protected $appends = ['edad', 'edad_al_31_marzo'];

    

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->apellido_paterno} {$this->apellido_materno} {$this->nombres}");
    }

    public function getEdadAttribute(): ?int
    {
        return $this->fecha_nacimiento
            ? $this->fecha_nacimiento->age
            : null;
    }

    /**
     * Calcula la edad del estudiante al 31 de marzo del año actual
     * Útil para reportes del censo escolar (Tabla 201)
     */
    public function getEdadAl31MarzoAttribute(): ?int
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }
        
        $fechaCorte = Carbon::create(now()->year, 3, 31);
        return $this->fecha_nacimiento->diffInYears($fechaCorte);
    }

    public function apoderado(): BelongsTo
    {
        // Si tu FK es apoderado_id y la PK de apoderados es id, está perfecto así:
        return $this->belongsTo(Apoderado::class, 'apoderado_id', 'id');
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'estudiante_id', 'id');
    }

    public function notas()
    {
        return $this->hasManyThrough(
            Nota::class,
            Matricula::class,
            'estudiante_id', // FK en matriculas
            'matricula_id', // FK en notas
            'id', // PK en estudiantes
            'id' // PK en matriculas
        );
    }

    /**
     * Relación con Usuario (cuenta de acceso al portal)
     */
    public function usuario()
    {
        return $this->hasOne(Usuario::class, 'estudiante_id');
    }
}

