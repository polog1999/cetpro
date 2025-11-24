<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Apoderado;
use App\Enums\EstadoCivil;
use App\Enums\TipoGenero;
use App\Enums\TipoDocumento;
use App\Enums\GradoInstruccion;
use App\Enums\Provincia;
use App\Enums\DistritoLima;

class Estudiante extends Model
{
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
        'grado_instruccion',   // ENUM (GradoInstruccion)
        'provincia',           // ENUM (Provincia) - solo Lima
        'distrito',            // ENUM (DistritoLima)
        'apoderado_id',
    ];

    protected $casts = [
        'fecha_nacimiento'  => 'date',
        'genero'            => TipoGenero::class,
        'estado_civil'      => EstadoCivil::class,
        'grado_instruccion' => GradoInstruccion::class,
        'tipo_documento'    => TipoDocumento::class,
        'provincia'         => Provincia::class,
        'distrito'          => DistritoLima::class,
    ];

    // Para poder acceder a $estudiante->edad
    protected $appends = ['edad'];

    

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellido_paterno} {$this->apellido_materno}");
    }

    public function getEdadAttribute(): ?int
    {
        return $this->fecha_nacimiento
            ? $this->fecha_nacimiento->age
            : null;
    }

    public function apoderado(): BelongsTo
    {
        // Si tu FK es apoderado_id y la PK de apoderados es id, está perfecto así:
        return $this->belongsTo(Apoderado::class, 'apoderado_id', 'id');
    }

    public function matriculas()
    {
        return $this->hasMany(\App\Models\Matricula::class, 'estudiante_id', 'id');
    }
}
