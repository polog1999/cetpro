# Plan de Refactorización Arquitectura - CETPRO-MDLM

## 🎯 Objetivo

Separar **toda la lógica de negocio** de los Filament Resources (Forms, Pages, Tables) y moverla a la capa de **Services**, siguiendo la arquitectura correcta:

```
Controllers/Resources → Services → Repositories → Models
```

---

## 📊 Resumen Ejecutivo

### Estado Actual (Auditado 11-12-2024)

| Resource | Líneas en Form | Lógica de Negocio | Prioridad | Estado |
|----------|----------------|-------------------|-----------|--------|
| **Matricula** | 597 | ❌ CRÍTICO | 🔴 ALTA | Refactor completo |
| **Horario** | 264 | ❌ CRÍTICO | 🔴 ALTA | Refactor completo |
| **Role** | 202 | ⚠️ MEDIO | 🟡 MEDIA | Refactor parcial |
| **Estudiante** | 116 | ✅ BIEN | 🟢 BAJA | Mantener |
| **Usuario** | 59 | ✅ BIEN | 🟢 BAJA | Mantener |
| **Programa** | 49 | ✅ BIEN | 🟢 BAJA | Mantener |
| **Pago** | 42 | ✅ BIEN | 🟢 BAJA | Mantener |
| **Apoderado** | ~40 | ✅ BIEN | 🟢 BAJA | Mantener |
| **Docente** | ~40 | ✅ BIEN | 🟢 BAJA | Mantener |
| **Empleado** | ~40 | ✅ BIEN | 🟢 BAJA | Mantener |
| **Especialidad** | ~35 | ✅ BIEN | 🟢 BAJA | Mantener |
| **Cronograma** | ~30 | ✅ BIEN | 🟢 BAJA | Mantener |

### Hallazgos Principales

✅ **Lo que está bien:**
- **EstudianteForm**: Solo validaciones de UI, sin lógica de negocio
- **ProgramaForm**: Simple, solo campos de formulario
- **UsuarioForm**: Delega a UserService correctamente
- **API Controllers**: 100% correctos, usan Services

❌ **Lo que necesita refactorización URGENTE:**

1. **MatriculaForm.php (597 líneas)** 
   - Validación de vacantes
   - Validación de duplicados
   - Generación de código de inscripción
   - Creación de Estudiante+Apoderado
   - Consultas complejas a BD
   - Lógica de formateo

2. **HorarioForm.php (264 líneas)**
   - Validación de conflictos de horario
   - Consultas complejas de docentes y aulas
   - Lógica de superposición de horarios  
   - Creación de Docente inline
   - Consulta y formateo de cursos

3. **RoleForm.php + CreateRole/EditRole (202+44+62 líneas)**
   - Extracción de permisos desde toggles
   - Sincronización de relaciones many-to-many
   - Lógica de transformación de datos

---

## 🔴 PRIORIDAD ALTA: MatriculaForm

### Problemas Identificados

#### 1. Validación de Vacantes (Líneas 402-413)

**❌ ACTUAL (MatriculaForm.php):**
```php
->rule(function (Get $get) {
    return function (string $attribute, $value, \Closure $fail) use ($get) {
        // Validar vacantes
        $horario = Horario::find($value);
        if ($horario) {
            $matriculados = Matricula::where('horario_id', $value)
                ->where('estado', '!=', EstadoMatricula::ANULADO)
                ->count();
            
            if ($matriculados >= $horario->vacantes) {
                $fail('No hay vacantes disponibles en este horario.');
            }
        }
    };
})
```

**✅ CORRECTO:**

**Crear `MatriculaService.php`:**
```php
public function validarVacantesDisponibles(int $horarioId): array
{
    $horario = $this->horarios->find($horarioId);
    
    if (!$horario) {
        return [
            'valido' => false,
            'mensaje' => 'El horario no existe.'
        ];
    }
    
    $matriculados = $this->matriculas->contarActivos($horarioId);
    
    if ($matriculados >= $horario->vacantes) {
        return [
            'valido' => false,
            'mensaje' => 'No hay vacantes disponibles en este horario.',
            'disponibles' => 0,
            'total' => $horario->vacantes
        ];
    }
    
    return [
        'valido' => true,
        'disponibles' => $horario->vacantes - $matriculados,
        'total' => $horario->vacantes
    ];
}
```

**Usar en Form:**
```php
->rule(function (Get $get) {
    return function (string $attribute, $value, \Closure $fail) use ($get) {
        $service = app(MatriculaService::class);
        
        $resultado = $service->validarVacantesDisponibles(value);
        
        if (!$resultado['valido']) {
            $fail($resultado['mensaje']);
        }
    };
})
```

---

#### 2. Validación de Duplicados (Líneas 415-426)

**❌ ACTUAL:**
```php
// 2. Validar duplicado
$estudianteId = $get('estudiante_id');
if ($estudianteId) {
    $exists = Matricula::where('estudiante_id', $estudianteId)
        ->where('horario_id', $value)
        ->where('estado', '!=', EstadoMatricula::ANULADO)
        ->exists();
    
    if ($exists) {
        $fail('El estudiante ya está matriculado en este horario.');
    }
}
```

**✅ CORRECTO:**

**MatriculaService.php:**
```php
public function validarDuplicado(int $estudianteId, int $horarioId, ?int $matriculaIdIgnorar = null): array
{
    $existe = $this->matriculas->existeMatriculaActiva(
        $estudianteId, 
        $horarioId, 
        $matriculaIdIgnorar
    );
    
    if ($existe) {
        return [
            'valido' => false,
            'mensaje' => 'El estudiante ya está matriculado en este horario.'
        ];
    }
    
    return ['valido' => true];
}
```

**MatriculaRepository.php:**
```php
public function existeMatriculaActiva(
    int $estudianteId, 
    int $horarioId, 
    ?int $ignorar = null
): bool {
    $query = Matricula::where('estudiante_id', $estudianteId)
        ->where('horario_id', $horarioId)
        ->where('estado', '!=', EstadoMatricula::ANULADO);
    
    if ($ignorar) {
        $query->where('id', '!=', $ignorar);
    }
    
    return $query->exists();
}
```

---

#### 3. Generación de Código de Inscripción (Líneas 560-595)

**❌ ACTUAL (MatriculaForm.php):**
```php
protected static function generarCodigoInscripcion(Set $set, Get $get): void
{
    $horarioId = $get('horario_id');
    
    if (! $horarioId) {
        $set('codigo_inscripcion', null);
        return;
    }

    $horario = Horario::find($horarioId);
    
    if (! $horario || ! $horario->id_programa) {
        $set('codigo_inscripcion', null);
        return;
    }

    $year = now()->format('Y');
    $programaId = str_pad($horario->id_programa, 3, '0', STR_PAD_LEFT);
    $prefijo = "{$year}-{$programaId}";
    $count = \App\Models\Matricula::where('codigo_inscripcion', 'like', "{$prefijo}-%")
        ->count();
    
    $secuencial = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    $codigo = "{$year}-{$programaId}-{$secuencial}";

    $set('codigo_inscripcion', $codigo);
}
```

**✅ CORRECTO:**

**MatriculaService.php:**
```php
public function generarCodigoInscripcion(int $horarioId): ?string
{
    $horario = $this->horarios->find($horarioId);
    
    if (!$horario || !$horario->id_programa) {
        return null;
    }

    $year = now()->format('Y');
    $programaId = str_pad($horario->id_programa, 3, '0', STR_PAD_LEFT);
    $prefijo = "{$year}-{$programaId}";
    
    $count = $this->matriculas->contarPorPrefijocodigo($prefijo);
    
    $secuencial = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    
    return "{$year}-{$programaId}-{$secuencial}";
}
```

**MatriculaRepository.php:**
```php
public function contarPorPrefijoCodigo(string $prefijo): int
{
    return Matricula::where('codigo_inscripcion', 'like', "{$prefijo}-%")
        ->count();
}

public function contarActivos(int $horarioId): int
{
    return Matricula::where('horario_id', $horarioId)
        ->where('estado', '!=', EstadoMatricula::ANULADO)
        ->count();
}
```

**MatriculaForm.php (SIMPLIFICADO):**
```php
->afterStateUpdated(function ($state, Set $set, Get $get) {
    if ($state) {
        $service = app(MatriculaService::class);
        $codigo = $service->generarCodigoInscripcion($state);
        $set('codigo_inscripcion', $codigo);
    }
})
```

---

#### 4. Creación de Estudiante+Apoderado (Líneas 176-221)

**❌ ACTUAL (MatriculaForm.php):**
```php
->createOptionUsing(function (array $data): int {
    // 1) Verificar si se proporcionaron datos del apoderado
    $apoderadoId = null;
    
    if (
        !empty($data['apoderado_tipo_documento']) || 
        !empty($data['apoderado_nro_documento']) || 
        !empty($data['apoderado_nombres'])
    ) {
        $apoderado = Apoderado::create([
            'tipo_documento'   => $data['apoderado_tipo_documento'] ?? null,
            'nro_documento'    => $data['apoderado_nro_documento'] ?? null,
            // ... más campos
        ]);
        
        $apoderadoId = $apoderado->id;
    }

    // 2) Datos del estudiante
    $estudianteData = [
        'tipo_documento' => $data['tipo_documento'] ?? null,
        // ... más campos
        'apoderado_id' => $apoderadoId,
    ];

    $estudiante = Estudiante::create($estudianteData);

    return $estudiante->getKey();
})
```

**✅ CORRECTO:**

**EstudianteService.php (NUEVO MÉTODO):**
```php
public function crearConApoderado(array $estudianteData, ?array $apoderadoData = null): Estudiante
{
    // Validar documento no duplicado
    if (isset($estudianteData['tipo_documento'], $estudianteData['nro_documento'])) {
        if ($this->estudiantes->findByDocumento(
            $estudianteData['tipo_documento'], 
            $estudianteData['nro_documento']
        )) {
            throw ValidationException::withMessages([
                'nro_documento' => 'Ya existe un estudiante con este número de documento.',
            ]);
        }
    }
    
    // Crear apoderado si hay datos
    $apoderadoId = null;
    
    if ($apoderadoData && !empty($apoderadoData['nombres'])) {
        // Validar apoderado no duplicado (opcional)
        if (isset($apoderadoData['nro_documento']) && 
            $this->apoderados->findByDocumento($apoderadoData['nro_documento'])) {
            throw ValidationException::withMessages([
                'apoderado_nro_documento' => 'Ya existe un apoderado con este número de documento.',
            ]);
        }
        
        $apoderado = $this->apoderados->create($apoderadoData);
        $apoderadoId = $apoderado->id;
    }
    
    // Agregar apoderado_id a estudiante
    $estudianteData['apoderado_id'] = $apoderadoId;
    
    // Crear estudiante
    return $this->estudiantes->create($estudianteData);
}
```

**MatriculaForm.php (SIMPLIFICADO):**
```php
->createOptionUsing(function (array $data): int {
    $service = app(EstudianteService::class);
    
    // Separar datos de estudiante y apoderado
    $apoderadoData = array_filter([
        'tipo_documento' => $data['apoderado_tipo_documento'] ?? null,
        'nro_documento' => $data['apoderado_nro_documento'] ?? null,
        'nombres' => $data['apoderado_nombres'] ?? null,
        'apellido_paterno' => $data['apoderado_apellido_paterno'] ?? null,
        'apellido_materno' => $data['apoderado_apellido_materno'] ?? null,
        'telefono' => $data['apoderado_telefono'] ?? null,
    ]);
    
    $estudianteData = [
        'tipo_documento' => $data['tipo_documento'],
        'nro_documento' => $data['nro_documento'],
        'nombres' => $data['nombres'],
        'apellido_paterno' => $data['apellido_paterno'],
        'apellido_materno' => $data['apellido_materno'],
        'genero' => $data['genero'] ?? null,
        'estado_civil' => $data['estado_civil'] ?? null,
        'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
        'telefono' => $data['telefono'] ?? null,
        'direccion' => $data['direccion'] ?? null,
        'email' => $data['email'] ?? null,
        'grado_instruccion' => $data['grado_instruccion'] ?? null,
        'provincia' => $data['provincia'] ?? null,
        'distrito' => $data['distrito'] ?? null,
    ];
    
    $estudiante = $service->crearConApoderado($estudianteData, $apoderadoData);
    
    return $estudiante->getKey();
})
```

---

#### 5. Consulta de Cursos de Horario (Líneas 504-553)

**❌ ACTUAL:**
```php
protected static function fillCursosDeHorario($horarioId, Set $set, Get $get): void
{
    if (! $horarioId) {
        $set('cursos_matriculados', null);
        return;
    }

    $horario = Horario::find($horarioId);

    if (! $horario) {
        $set('cursos_matriculados', 'Horario no encontrado.');
        return;
    }

    $cursos = Curso::query()
        ->where('id_programa', $horario->id_programa)
        ->orderBy('nombre_curso')
        ->get();

    if ($cursos->isEmpty()) {
        // Lógica de mensaje
        $set('cursos_matriculados', $mensaje);
        return;
    }

    $texto = $cursos
        ->values()
        ->map(function ($curso, $index) {
            // Formateo complejo
        })
        ->implode(PHP_EOL);

    $set('cursos_matriculados', $texto);
}
```

**✅ CORRECTO:**

**HorarioService.php (NUEVO):**
```php
public function obtenerCursosFormateados(int $horarioId, TipoMatricula $tipoMatricula): array
{
    $horario = $this->horarios->find($horarioId);
    
    if (!$horario) {
        return [
            'success' => false,
            'texto' => 'Horario no encontrado.'
        ];
    }
    
    $cursos = $this->cursos->findByPrograma($horario->id_programa);
    
    if ($cursos->isEmpty()) {
        $mensaje = ($tipoMatricula === TipoMatricula::PROGRAMA || 
                    $tipoMatricula === TipoMatricula::MODULO)
            ? 'Este programa no tiene módulos registrados.'
            : 'Esta formación continua no tiene cursos registrados.';
            
        return [
            'success' => false,
            'texto' => $mensaje
        ];
    }
    
    $texto = $cursos->values()->map(function ($curso, $index) {
        $n = $index + 1;
        $nombre = $curso->nombre_curso;
        
        $fechaInicio = $curso->fecha_inicio 
            ? \Carbon\Carbon::parse($curso->fecha_inicio)->format('d/m/Y')
            : 'Sin fecha';
            
        $fechaFin = $curso->fecha_termino 
            ? \Carbon\Carbon::parse($curso->fecha_termino)->format('d/m/Y')
            : 'Sin fecha';
        
        return "{$n}. {$nombre} | Inicio: {$fechaInicio} | Fin: {$fechaFin}";
    })->implode(PHP_EOL);
    
    return [
        'success' => true,
        'texto' => $texto,
        'cursos' => $cursos
    ];
}
```

**CursoRepository.php (NUEVO MÉTODO):**
```php
public function findByPrograma(int $programaId): Collection
{
    return Curso::where('id_programa', $programaId)
        ->orderBy('nombre_curso')
        ->get();
}
```

**MatriculaForm.php (SIMPLIFICADO):**
```php
->afterStateUpdated(function ($state, Set $set, Get $get) {
    if ($state) {
        $service = app(HorarioService::class);
        $tipoMatricula = $get('tipo_matricula');
        
        $resultado = $service->obtenerCursosFormateados($state, $tipoMatricula);
        $set('cursos_matriculados', $resultado['texto']);
    }
})
```

---

#### 6. Creación Completa de Matrícula

**✅ MÉTODO CENTRAL:**

**MatriculaService.php (NUEVO MÉTODO):**
```php
/**
 * Crea una nueva matrícula con todas las validaciones
 */
public function crear(array $data): Matricula
{
    // 1. Validar vacantes disponibles
    $validacionVacantes = $this->validarVacantesDisponibles($data['horario_id']);
    if (!$validacionVacantes['valido']) {
        throw ValidationException::withMessages([
            'horario_id' => $validacionVacantes['mensaje']
        ]);
    }
    
    // 2. Validar matrícula no duplicada
    $validacionDuplicado = $this->validarDuplicado(
        $data['estudiante_id'], 
        $data['horario_id']
    );
    if (!$validacionDuplicado['valido']) {
        throw ValidationException::withMessages([
            'estudiante_id' => $validacionDuplicado['mensaje']
        ]);
    }
    
    // 3. Generar código de inscripción si no existe
    if (empty($data['codigo_inscripcion'])) {
        $data['codigo_inscripcion'] = $this->generarCodigoInscripcion($data['horario_id']);
    }
    
    // 4. Establecer valores por defecto
    $data['estado'] = $data['estado'] ?? EstadoMatricula::ACTIVO;
    $data['fecha_inscripcion'] = $data['fecha_inscripcion'] ?? now();
    
    // 5. Crear matrícula
    return $this->matriculas->create($data);
}

/**
 * Actualiza una matrícula existente con validaciones
 */
public function actualizar(int $matriculaId, array $data): Matricula
{
    $matricula = $this->matriculas->find($matriculaId);
    
    if (!$matricula) {
        throw ValidationException::withMessages([
            'matricula' => 'La matrícula no existe.'
        ]);
    }
    
    // No permitir modificar matrículas anuladas
    if ($matricula->estado === EstadoMatricula::ANULADO) {
        throw ValidationException::withMessages([
            'estado' => 'No se puede modificar una matrícula anulada.'
        ]);
    }
    
    // Si cambia el horario, validar vacantes y duplicados
    if (isset($data['horario_id']) && $data['horario_id'] !== $matricula->horario_id) {
        $validacionVacantes = $this->validarVacantesDisponibles($data['horario_id']);
        if (!$validacionVacantes['valido']) {
            throw ValidationException::withMessages([
                'horario_id' => $validacionVacantes['mensaje']
            ]);
        }
        
        $validacionDuplicado = $this->validarDuplicado(
            $matricula->estudiante_id,
            $data['horario_id'],
            $matriculaId
        );
        if (!$validacionDuplicado['valido']) {
            throw ValidationException::withMessages([
                'horario_id' => $validacionDuplicado['mensaje']
            ]);
        }
    }
    
    return $this->matriculas->update($matricula, $data);
}
```

---

## 🔴 PRIORIDAD ALTA: HorarioForm

### Problemas Identificados

#### 1. Validación de Conflictos de Horario (Líneas 195-241)

**❌ ACTUAL (HorarioForm.php):**
```php
->rules([
    function (Get $get, ?\\App\\Models\\Horario $record) {
        return function (string $attribute, $value, \\Closure $fail) use ($get, $record) {
            $docenteId = $get('id_docente');
            $aula = $get('aula');
            $dias = $get('dias');
            $horaInicio = $get('hora_inicio');
            $horaFin = $value;

            if (! $docenteId || ! $dias || ! $horaInicio || ! $horaFin) {
                return;
            }

            $query = \\App\\Models\\Horario::query();

            if ($record) {
                $query->where('id_horario', '!=', $record->id_horario);
            }

            $query->where(function ($q) use ($docenteId, $aula) {
                $q->where('id_docente', $docenteId);
                if ($aula) {
                    $q->orWhere('aula', $aula);
                }
            });

            // Verificar superposición de días
            $query->where(function ($q) use ($dias) {
                foreach ($dias as $dia) {
                    $q->orWhereJsonContains('dias', $dia);
                }
            });

            // Verificar superposición de horas
            $query->where(function ($q) use ($horaInicio, $horaFin) {
                $q->where('hora_inicio', '<', $horaFin)
                  ->where('hora_fin', '>', $horaInicio);
            });

            if ($query->exists()) {
                $fail('Existe un cruce de horarios para el docente o el aula seleccionada.');
            }
        };
    },
])
```

**✅ CORRECTO:**

**CREAR `HorarioService.php`:**
```php
<?php

namespace App\Services;

use App\Repositories\HorarioRepositoryInterface;
use App\Repositories\DocenteRepositoryInterface;
use App\Repositories\CursoRepositoryInterface;
use App\Models\Horario;
use App\Enums\TipoMatricula;
use Illuminate\Support\Collection;

class HorarioService
{
    public function __construct(
        private HorarioRepositoryInterface $horarios,
        private DocenteRepositoryInterface $docentes,
        private CursoRepositoryInterface $cursos
    ) {}
    
    /**
     * Valida si existe conflicto de horarios para un docente o aula
     */
    public function validarConflictoHorario(
        int $docenteId,
        array $dias,
        string $horaInicio,
        string $horaFin,
        ?string $aula = null,
        ?int $horarioIdIgnorar = null
    ): array {
        // Validar entrada
        if (empty($dias)) {
            return ['valido' => true];
        }
        
        // Buscar conflictos para el docente
        $conflictosDocente = $this->horarios->findConflictosHorario(
            $docenteId,
            $dias,
            $horaInicio,
            $horaFin,
            $horarioIdIgnorar
        );
        
        if ($conflictosDocente->isNotEmpty()) {
            $docente = $this->docentes->find($docenteId);
            $nombreDocente = $docente?->nombre_completo ?? 'el docente';
            
            return [
                'valido' => false,
                'mensaje' => "Existe un cruce de horarios para {$nombreDocente}.",
                'conflictos' => $conflictosDocente
            ];
        }
        
        // Buscar conflictos para el aula (si hay aula especificada)
        if ($aula) {
            $conflictosAula = $this->horarios->findConflictosAula(
                $aula,
                $dias,
                $horaInicio,
                $horaFin,
                $horarioIdIgnorar
            );
            
            if ($conflictosAula->isNotEmpty()) {
                return [
                    'valido' => false,
                    'mensaje' => "El aula {$aula} ya está ocupada en ese horario.",
                    'conflictos' => $conflictosAula
                ];
            }
        }
        
        return ['valido' => true];
    }
    
    /**
     * Obtiene los cursos formateados de un horario
     */
    public function obtenerCursosFormateados(int $horarioId, TipoMatricula $tipoMatricula): array
    {
        // Ver implementación anterior
    }
}
```

**CREAR `HorarioRepositoryInterface.php` (NUEVOS MÉTODOS):**
```php
public function findConflictosHorario(
    int $docenteId,
    array $dias,
    string $horaInicio,
    string $horaFin,
    ?int $ignorarId = null
): Collection;

public function findConflictosAula(
    string $aula,
    array $dias,
    string $horaInicio,
    string $horaFin,
    ?int $ignorarId = null
): Collection;
```

**HorarioRepository.php (IMPLEMENTACIÓN):**
```php
public function findConflictosHorario(
    int $docenteId,
    array $dias,
    string $horaInicio,
    string $horaFin,
    ?int $ignorarId = null
): Collection {
    $query = Horario::where('id_docente', $docenteId);
    
    if ($ignorarId) {
        $query->where('id_horario', '!=', $ignorarId);
    }
    
    // Verificar superposición de días
    $query->where(function ($q) use ($dias) {
        foreach ($dias as $dia) {
            $q->orWhereJsonContains('dias', $dia);
        }
    });
    
    // Verificar superposición de horas
    $query->where(function ($q) use ($horaInicio, $horaFin) {
        $q->where('hora_inicio', '<', $horaFin)
          ->where('hora_fin', '>', $horaInicio);
    });
    
    return $query->get();
}

public function findConflictosAula(
    string $aula,
    array $dias,
    string $horaInicio,
    string $horaFin,
    ?int $ignorarId = null
): Collection {
    $query = Horario::where('aula', $aula);
    
    if ($ignorarId) {
        $query->where('id_horario', '!=', $ignorarId);
    }
    
    // Verificar superposición de días
    $query->where(function ($q) use ($dias) {
        foreach ($dias as $dia) {
            $q->orWhereJsonContains('dias', $dia);
        }
    });
    
    // Verificar superposición de horas
    $query->where(function ($q) use ($horaInicio, $horaFin) {
        $q->where('hora_inicio', '<', $horaFin)
          ->where('hora_fin', '>', $horaInicio);
    });
    
    return $query->get();
}
```

**HorarioForm.php (SIMPLIFICADO):**
```php
->rules([
    function (Get $get, ?\\App\\Models\\Horario $record) {
        return function (string $attribute, $value, \\Closure $fail) use ($get, $record) {
            $docenteId = $get('id_docente');
            $aula = $get('aula');
            $dias = $get('dias');
            $horaInicio = $get('hora_inicio');
            $horaFin = $value;

            if (!$docenteId || !$dias || !$horaInicio || !$horaFin) {
                return;
            }

            $service = app(HorarioService::class);
            
            $resultado = $service->validarConflictoHorario(
                $docenteId,
                $dias,
                $horaInicio,
                $horaFin,
                $aula,
                $record?->id_horario
            );
            
            if (!$resultado['valido']) {
                $fail($resultado['mensaje']);
            }
        };
    },
])
```

---

#### 2. Creación de Docente Inline (Líneas 180-182)

**❌ ACTUAL:**
```php
->createOptionUsing(function (array $data) {
    return Docente::create($data)->getKey();
})
```

**✅ CORRECTO:**

**HorarioForm.php:**
```php
->createOptionUsing(function (array $data) {
    $service = app(DocenteService::class);
    $docente = $service->crear($data);
    return $docente->getKey();
})
```

**DocenteService.php (YA EXISTE, PERO VERIFICAR):**
```php
public function crear(array $data): Docente
{
    // Validar documento no duplicado
    if (isset($data['tipo_documento'], $data['nro_documento'])) {
        if ($this->docentes->findByDocumento(
            $data['tipo_documento'], 
            $data['nro_documento']
        )) {
            throw ValidationException::withMessages([
                'nro_documento' => 'Ya existe un docente con este número de documento.',
            ]);
        }
    }
    
    return $this->docentes->create($data);
}
```

---

#### 3. Consulta y Formateo de Cursos (Líneas 66-92)

**❌ ACTUAL:**
```php
->afterStateUpdated(function (Get $get, Set $set) {
    $id = $get('id_programa');

    if (! $id) {
        $set('cursos_programa', null);
        return;
    }

    $programa = Programa::with('cursos')->find($id);

    if (! $programa || $programa->cursos->isEmpty()) {
        $set('cursos_programa', 'No hay cursos asignados a este programa.');
        return;
    }

    $texto = $programa->cursos
        ->map(function ($curso) {
            $nombre = $curso->nombre_curso ?? $curso->nombre ?? 'Sin nombre';
            return '- ' . $nombre;
        })
        ->implode(PHP_EOL);

    $set('cursos_programa', $texto);
})
```

**✅ CORRECTO:**

**ProgramaService.php (NUEVO):**
```php
<?php

namespace App\Services;

use App\Repositories\ProgramaRepositoryInterface;
use App\Repositories\CursoRepositoryInterface;
use App\Models\Programa;

class ProgramaService
{
    public function __construct(
        private ProgramaRepositoryInterface $programas,
        private CursoRepositoryInterface $cursos
    ) {}
    
    public function obtenerCursosFormateadosSimple(int $programaId): string
    {
        $cursos = $this->cursos->findByPrograma($programaId);
        
        if ($cursos->isEmpty()) {
            return 'No hay cursos asignados a este programa.';
        }
        
        return $cursos
            ->map(fn($curso) => '- ' . ($curso->nombre_curso ?? 'Sin nombre'))
            ->implode(PHP_EOL);
    }
}
```

**HorarioForm.php (SIMPLIFICADO):**
```php
->afterStateUpdated(function (Get $get, Set $set) {
    $id = $get('id_programa');

    if (!$id) {
        $set('cursos_programa', null);
        return;
    }

    $service = app(ProgramaService::class);
    $texto = $service->obtenerCursosFormateadosSimple($id);
    $set('cursos_programa', $texto);
})
```

---

## 🟡 PRIORIDAD MEDIA: RoleForm + Pages

### Problemas Identificados

#### 1. Extracción y Sincronización de Permisos

**❌ ACTUAL:**

**RoleForm.php:**
```php
public static function extractPermisosFromToggles(array $data): array
{
    $permisosIds = [];

    foreach ($data as $key => $value) {
        if (str_starts_with($key, 'permiso_') && $value === true) {
            $permisoId = (int) str_replace('permiso_', '', $key);
            $permisosIds[] = $permisoId;
        }
    }

    return $permisosIds;
}

public static function fillPermisosToggles($role): array
{
    $data = [];

    if ($role->permisos) {
        foreach ($role->permisos as $permiso) {
            $data["permiso_{$permiso->id}"] = true;
        }
    }

    return $data;
}
```

**CreateRole.php:**
```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    $this->permisosToSync = RoleForm::extractPermisosFromToggles($data);

    foreach ($data as $key => $value) {
        if (str_starts_with($key, 'permiso_')) {
            unset($data[$key]);
        }
    }

    return $data;
}

protected function afterCreate(): void
{
    if (!empty($this->permisosToSync) && !$this->record->es_admin) {
        $this->record->permisos()->sync($this->permisosToSync);
    }
}
```

**✅ CORRECTO:**

**RoleService.php (YA EXISTE, AGREGAR MÉTODOS):**
```php
<?php

namespace App\Services;

use App\Repositories\RoleRepositoryInterface;
use App\Repositories\PermisoRepositoryInterface;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RoleService
{
    public function __construct(
        private RoleRepositoryInterface $roles,
        private PermisoRepositoryInterface $permisos
    ) {}
    
    /**
     * Crea un rol con sus permisos
     */
    public function crearConPermisos(array $roleData, array $permisosIds): Role
    {
        // Validar nombre único
        if ($this->roles->findByNombre($roleData['nombre'])) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe un rol con este nombre.',
            ]);
        }
        
        // Crear rol
        $role = $this->roles->create($roleData);
        
        // Asignar permisos solo si NO es admin
        if (!$role->es_admin && !empty($permisosIds)) {
            $this->asignarPermisos($role->id, $permisosIds);
        }
        
        return $role->fresh('permisos');
    }
    
    /**
     * Actualiza un rol y sus permisos
     */
    public function actualizarConPermisos(int $roleId, array $roleData, array $permisosIds): Role
    {
        $role = $this->roles->find($roleId);
        
        if (!$role) {
            throw ValidationException::withMessages([
                'role' => 'El rol no existe.',
            ]);
        }
        
        // Validar nombre único (ignorando el rol actual)
        $existente = $this->roles->findByNombre($roleData['nombre']);
        if ($existente && $existente->id !== $roleId) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe otro rol con este nombre.',
            ]);
        }
        
        // Actualizar rol
        $role = $this->roles->update($role, $roleData);
        
        // Sincronizar permisos
        if ($role->es_admin) {
            // Si es admin, quitar todos los permisos
            $role->permisos()->sync([]);
        } else {
            $this->asignarPermisos($role->id, $permisosIds);
        }
        
        return $role->fresh('permisos');
    }
    
    /**
     * Asigna permisos a un rol
     */
    public function asignarPermisos(int $roleId, array $permisosIds): void
    {
        $role = $this->roles->find($roleId);
        
        if (!$role) {
            throw ValidationException::withMessages([
                'role' => 'El rol no existe.',
            ]);
        }
        
        if ($role->es_admin) {
            throw ValidationException::withMessages([
                'role' => 'Los roles de administrador no necesitan permisos específicos.',
            ]);
        }
        
        // Validar que todos los permisos existen
        $permisosExistentes = $this->permisos->findByIds($permisosIds);
        
        if ($permisosExistentes->count() !== count($permisosIds)) {
            throw ValidationException::withMessages([
                'permisos' => 'Algunos permisos seleccionados no existen.',
            ]);
        }
        
        $role->permisos()->sync($permisosIds);
    }
    
    /**
     * Extrae IDs de permisos desde toggles del formulario
     */
    public function extraerPermisosDeToggles(array $formData): array
    {
        $permisosIds = [];
        
        foreach ($formData as $key => $value) {
            if (str_starts_with($key, 'permiso_') && $value === true) {
                $permisoId = (int) str_replace('permiso_', '', $key);
                $permisosIds[] = $permisoId;
            }
        }
        
        return $permisosIds;
    }
    
    /**
     * Prepara datos de toggles para cargar en formulario
     */
    public function prepararTogglesPermisos(Role $role): array
    {
        $data = [];
        
        if ($role->permisos) {
            foreach ($role->permisos as $permiso) {
                $data["permiso_{$permiso->id}"] = true;
            }
        }
        
        return $data;
    }
}
```

**CreateRole.php (SIMPLIFICADO):**
```php
<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Services\RoleService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $service = app(RoleService::class);
        
        // Extraer permisos
        $permisosIds = $service->extraerPermisosDeToggles($data);
        
        // Limpiar toggles del data
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permiso_')) {
                unset($data[$key]);
            }
        }
        
        // Crear con permisos
        return $service->crearConPermisos($data, $permisosIds);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
```

**EditRole.php (SIMPLIFICADO):**
```php
<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Services\RoleService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $service = app(RoleService::class);
        
        // Cargar toggles de permisos
        $togglesData = $service->prepararTogglesPermisos($this->record);
        
        return array_merge($data, $togglesData);
    }

    protected function handleRecordUpdate($record, array $data): Model
    {
        $service = app(RoleService::class);
        
        // Extraer permisos
        $permisosIds = $service->extraerPermisosDeToggles($data);
        
        // Limpiar toggles del data
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'permiso_')) {
                unset($data[$key]);
            }
        }
        
        // Actualizar con permisos
        return $service->actualizarConPermisos($record->id, $data, $permisosIds);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
```

---

## ✅ SERVICIOS QUE NECESITAN CREARSE

### 1. HorarioService (NUEVO)

**Archivo:** `app/Services/HorarioService.php`

**Responsabilidades:**
- Validar conflictos de horarios (docente/aula)
- Obtener cursos formateados de un horario
- Validar asignación de docentes

### 2. ProgramaService (NUEVO)

**Archivo:** `app/Services/ProgramaService.php`

**Responsabilidades:**
- Obtener cursos formateados de un programa
- Validar datos de programas

### 3. CursoService (CONSIDERADO - OPCIONAL)

**Archivo:** `app/Services/CursoService.php`

**Responsabilidades:**
- Lógica de negocio de cursos (si hay)

---

## ✅ REPOSITORIES QUE NECESITAN MÉTODOS NUEVOS

### MatriculaRepository

**Nuevos métodos:**
```php
public function contarPorPrefijoCodigo(string $prefijo): int;
public function contarActivos(int $horarioId): int;
public function existeMatriculaActiva(int $estudianteId, int $horarioId, ?int $ignorar = null): bool;
```

### HorarioRepository

**Nuevos métodos:**
```php
public function findConflictosHorario(
    int $docenteId,
    array $dias,
    string $horaInicio,
    string $horaFin,
    ?int $ignorarId = null
): Collection;

public function findConflictosAula(
    string $aula,
    array $dias,
    string $horaInicio,
    string $horaFin,
    ?int $ignorarId = null
): Collection;
```

### CursoRepository

**Nuevos métodos:**
```php
public function findByPrograma(int $programaId): Collection;
```

### ApoderadoRepository

**Nuevos métodos:**
```php
public function findByDocumento(string $nroDocumento): ?Apoderado;
```

### RoleRepository

**Nuevos métodos:**
```php
public function findByNombre(string $nombre): ?Role;
```

### PermisoRepository

**Nuevos métodos:**
```php
public function findByIds(array $ids): Collection;
```

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

### Fase 0: Pre-refactorización (0.5 día)

- [ ] Crear branch de refactorización `git checkout -b refactor/architecture-services`
- [ ] Asegurar que tests actuales pasen `php artisan test`
- [ ] Documentar comportamiento actual (screenshots de formularios)
- [ ] Crear backup de archivos a modificar

### Fase 1: Preparación (1-2 días)

- [ ] Crear `HorarioService.php`
- [ ] Crear `ProgramaService.php`
- [ ] Agregar métodos a `MatriculaRepository.php` y `MatriculaRepositoryInterface.php`
- [ ] Agregar métodos a `HorarioRepository.php` y `HorarioRepositoryInterface.php`
- [ ] Agregar métodos a `CursoRepository.php` y `CursoRepositoryInterface.php`
- [ ] Agregar métodos a `ApoderadoRepository.php` y `ApoderadoRepositoryInterface.php`
- [ ] Agregar métodos a `RoleRepository.php` y `RoleRepositoryInterface.php`
- [ ] Agregar métodos a `PermisoRepository.php` y `PermisoRepositoryInterface.php`
- [ ] Registrar nuevos Services y Repositories

**RepositoryServiceProvider.php:**
```php
public function register(): void
{
    // Existing bindings...
    
    // Curso Repository
    $this->app->bind(
        \App\Repositories\CursoRepositoryInterface::class,
        \App\Repositories\CursoRepository::class
    );
}
```

**AppServiceProvider.php (si no existe RepositoryServiceProvider):**
```php
public function register(): void
{
    // Services ya están auto-registrados por autowiring
    // Solo necesario si usas interfaces para Services
}
```

### Fase 2: Refactorización MatriculaForm (2-3 días)

- [ ] Implementar `MatriculaService::validarVacantesDisponibles()`
- [ ] Implementar `MatriculaService::validarDuplicado()`
- [ ] Implementar `MatriculaService::generarCodigoInscripcion()`
- [ ] Implementar `MatriculaService::crear()` (método central)
- [ ] Implementar `MatriculaService::actualizar()`
- [ ] Implementar `EstudianteService::crearConApoderado()`
- [ ] Implementar `HorarioService::obtenerCursosFormateados()`
- [ ] Refactorizar `MatriculaForm.php` para usar Services
- [ ] Probar creación y edición de matrículas

### Fase 3: Refactorización HorarioForm (1-2 días)

- [ ] Implementar `HorarioService::validarConflictoHorario()`
- [ ] Implementar `ProgramaService::obtenerCursosFormateadosSimple()`
- [ ] Refactorizar `HorarioForm.php` para usar Services
- [ ] Refactorizar creación de Docente inline
- [ ] Probar creación de horarios

### Fase 4: Refactorización RoleForm + Pages (1 día)

- [ ] Implementar `RoleService::crearConPermisos()`
- [ ] Implementar `RoleService::actualizarConPermisos()`
- [ ] Implementar `RoleService::extraerPermisosDeToggles()`
- [ ] Implementar `RoleService::prepararTogglesPermisos()`
- [ ] Refactorizar `CreateRole.php`
- [ ] Refactorizar `EditRole.php`
- [ ] Probar CRUD de roles

### Fase 5: Testing y Validación (1-2 días)

**Tests Unitarios de MatriculaService:**
- [ ] `test_validar_vacantes_disponibles_con_vacantes()`
- [ ] `test_validar_vacantes_disponibles_sin_vacantes()`
- [ ] `test_validar_duplicado_con_duplicado_existente()`
- [ ] `test_validar_duplicado_sin_duplicado()`
- [ ] `test_generar_codigo_inscripcion_secuencial()`
- [ ] `test_crear_matricula_completa()`

**Tests Unitarios de HorarioService:**
- [ ] `test_validar_conflicto_horario_con_conflicto_docente()`
- [ ] `test_validar_conflicto_horario_con_conflicto_aula()`
- [ ] `test_validar_conflicto_horario_sin_conflicto()`
- [ ] `test_obtener_cursos_formateados()`

**Tests Unitarios de RoleService:**
- [ ] `test_crear_rol_con_permisos()`
- [ ] `test_actualizar_rol_con_permisos()`
- [ ] `test_extraer_permisos_de_toggles()`

**Tests de Integración:**
- [ ] Crear matrícula completa con estudiante nuevo + apoderado
- [ ] Crear horario con validación de conflictos múltiples
- [ ] Crear rol con permisos y verificar sincronización
- [ ] Editar matrícula y verificar validaciones

**Tests Manuales:**
- [ ] Formulario de matrícula: crear, editar, validaciones
- [ ] Formulario de horario: crear con conflictos
- [ ] Formulario de roles: toggle de permisos
- [ ] Verificar mensajes de error en español

**Regresión:**
- [ ] Ejecutar suite completa de tests: `php artisan test`
- [ ] Verificar que API sigue funcionando correctamente
- [ ] Verificar todos los Filament Resources

---

### Fase 6: Post-refactorización (0.5 día)

- [ ] Comparar comportamiento con screenshots de Fase 0
- [ ] Actualizar `CHANGELOG.md` con los cambios
- [ ] Remover código comentado (backup)
- [ ] Generar documentación técnica actualizada
- [ ] Code review con equipo
- [ ] Merge a `develop`: `git checkout develop && git merge refactor/architecture-services`
- [ ] Tag de versión: `git tag -a v2.0.0-refactor -m "Architecture refactoring completed"`

---

## 📊 MÉTRICAS DE ÉXITO

### Antes

| Métrica | Valor |
|---------|-------|
| Líneas en MatriculaForm | 597 |
| Líneas en HorarioForm | 264 |
| Lógica en Services | ~40% |
| Duplicación de código | Alta |
| Testeabilidad | Baja |

### Después (Objetivo)

| Métrica | Valor Objetivo |
|---------|----------------|
| Líneas en MatriculaForm | ~150 (-75%) |
| Líneas en HorarioForm | ~100 (-62%) |
| Lógica en Services | ~90% |
| Duplicación de código | Mínima |
| Testeabilidad | Alta |

---

## 🎯 BENEFICIOS ESPERADOS

1. **Mantenibilidad**: Lógica centralizada en un solo lugar
2. **Testeabilidad**: Services pueden probarse unitariamente
3. **Reutilización**: Misma lógica usada por API y Filament
4. **Consistencia**: Validaciones idénticas en todos los puntos de entrada
5. **Escalabilidad**: Fácil agregar nueva lógica de negocio
6. **Claridad**: Separación clara de responsabilidades

---

## 📝 NOTAS IMPORTANTES

1. **No eliminar código hasta validar**: Comentar código antiguo, no borrarlo inmediatamente
2. **Probar incremental**: Cada cambio debe probarse antes de continuar
3. **Mantener compatibilidad**: API ya usa Services correctamente, no afectarla
4. **Documentar cambios**: Actualizar documentación técnica
5. **Coordinar con equipo**: Comunicar cambios antes de mergear

---

**Documento creado:** 11 de Diciembre de 2024  
**Autor:** Análisis técnico CETPRO-MDLM  
**Versión:** 1.0  
**Estado:** Plan de refactorización aprobado
