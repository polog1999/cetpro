<?php

namespace App\Services;

use App\Models\Matricula;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Curso;
use App\Enums\EstadoMatricula;
use App\Enums\TipoMatricula;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de dominio para la lógica de negocio de matrículas.
 * 
 * Este servicio centraliza todas las operaciones relacionadas con matrículas,
 * asegurando consistencia y reutilización de la lógica de negocio.
 */
class MatriculaService
{
    public function __construct(
        private \App\Repositories\MatriculaRepositoryInterface $matriculas,
        private \App\Repositories\HorarioRepositoryInterface $horarios
    ) {}
    
    /**
     * Valida si hay vacantes disponibles en un horario.
     *
     * @param int $horarioId
     * @return array ['valido' => bool, 'mensaje' => string, 'disponibles' => int, 'total' => int]
     */
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
    
    /**
     * Valida si ya existe una matrícula activa para el estudiante.
     * 
     * La validación depende del tipo de matrícula:
     * - PROGRAMA/FORMACION_CONTINUA: Solo una por horario
     * - CURSO: Solo una por curso específico (permite múltiples cursos del mismo programa)
     *
     * @param int $estudianteId
     * @param int $horarioId
     * @param int|null $matriculaIdIgnorar ID de matrícula a ignorar (para edición)
     * @param TipoMatricula|null $tipoMatricula Tipo de matrícula a validar
     * @param int|null $cursoId ID del curso (requerido para tipo CURSO)
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public function validarDuplicado(
        int $estudianteId, 
        int $horarioId, 
        ?int $matriculaIdIgnorar = null,
        ?TipoMatricula $tipoMatricula = null,
        ?int $cursoId = null,
        ?int $unidadId = null
    ): array {
        $existe = $this->tieneMatriculaDuplicada(
            $estudianteId, 
            $horarioId,
            $tipoMatricula,
            $cursoId,
            $matriculaIdIgnorar,
            $unidadId
        );
        
        if ($existe) {
            // Mensaje diferenciado según tipo de matrícula
            if ($tipoMatricula === TipoMatricula::CURSO) {
                return [
                    'valido' => false,
                    'mensaje' => 'El estudiante ya está matriculado en este curso.'
                ];
            }
            
            if ($tipoMatricula === TipoMatricula::MODULO) {
                return [
                    'valido' => false,
                    'mensaje' => 'El estudiante ya está matriculado en este módulo.'
                ];
            }
            
            if ($tipoMatricula === TipoMatricula::UNIDAD) {
                return [
                    'valido' => false,
                    'mensaje' => 'El estudiante ya está matriculado en esta unidad.'
                ];
            }
            
            return [
                'valido' => false,
                'mensaje' => 'El estudiante ya está matriculado en este programa o formación continua.'
            ];
        }
        
        return ['valido' => true];
    }
    
    /**
     * Verifica si un estudiante tiene deudas pendientes (pagos vencidos).
     * 
     * Un estudiante no puede matricularse en un nuevo curso/módulo si tiene
     * pagos vencidos en cualquiera de sus matrículas activas.
     *
     * @param int $estudianteId
     * @return array ['tiene_deuda' => bool, 'mensaje' => string|null]
     */
    public function estudianteTieneDeudas(int $estudianteId): array
    {
        $tieneDeuda = Matricula::where('estudiante_id', $estudianteId)
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->whereHas('cronograma.pagos', function ($query) {
                $query->whereRaw("LOWER(estado) LIKE '%vencido%'");
            })
            ->exists();
        
        if ($tieneDeuda) {
            return [
                'tiene_deuda' => true,
                'mensaje' => 'El estudiante tiene pagos vencidos pendientes. Debe regularizar sus pagos antes de matricularse en un nuevo curso.'
            ];
        }
        
        return [
            'tiene_deuda' => false,
            'mensaje' => null
        ];
    }
    
    /**
     * Genera un código de inscripción único para la matrícula.
     * 
     * Formato depende del tipo de matrícula:
     * - PROGRAMA/FORMACION_CONTINUA: AñoDNIHorarioID
     * - CURSO/MODULO: AñoDNIHorarioIDCursoID
     * - UNIDAD: AñoDNIHorarioIDCursoIDUnidadID
     * 
     * Ejemplos:
     * - Programa: 2026123456781 (año + DNI + horario 1)
     * - Módulo: 202612345678142 (año + DNI + horario 1 + curso 42)
     * - Unidad: 20261234567814215 (año + DNI + horario 1 + curso 42 + unidad 15)
     *
     * @param int $horarioId
     * @param int|null $estudianteId
     * @param int|null $cursoId ID del curso/módulo (opcional)
     * @param int|null $unidadId ID de la unidad (opcional)
     * @return string|null
     */
    public function generarCodigoInscripcion(
        int $horarioId, 
        ?int $estudianteId = null,
        ?int $cursoId = null,
        ?int $unidadId = null
    ): ?string {
        $horario = $this->horarios->find($horarioId);
        
        if (!$horario) {
            return null;
        }

        $year = now()->format('Y');
        
        // Obtener DNI del estudiante
        $dni = '00000000';
        if ($estudianteId) {
            $estudiante = Estudiante::find($estudianteId);
            $dni = $estudiante?->nro_documento ?? '00000000';
        }
        
        // Obtener ID del horario
        $horarioIdStr = $horario->id_horario ?? $horarioId;
        
        // Formato base: AñoDNIHorarioID
        $codigo = "{$year}{$dni}{$horarioIdStr}";
        
        // Añadir ID del curso/módulo si existe
        if ($cursoId) {
            $codigo .= $cursoId;
        }
        
        // Añadir ID de la unidad si existe
        if ($unidadId) {
            $codigo .= $unidadId;
        }
        
        return $codigo;
    }
    
    /**
     * Crea una nueva matrícula con todas las validaciones.
     *
     * @param array $data
     * @return Matricula
     * @throws ValidationException
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
            $data['codigo_inscripcion'] = $this->generarCodigoInscripcion(
                $data['horario_id'], 
                $data['estudiante_id'] ?? null,
                $data['id_curso'] ?? null,
                $data['id_unidad'] ?? null
            );
        }
        
        // 4. Establecer valores por defecto
        $data['estado'] = $data['estado'] ?? EstadoMatricula::ACTIVO;
        $data['fecha_inscripcion'] = $data['fecha_inscripcion'] ?? now();
        
        // 5. Crear matrícula
        $matricula = $this->matriculas->create($data);

        // 6. Asegurar que el estudiante tenga usuario para el portal
        try {
            $estudiante = Estudiante::find($data['estudiante_id']);
            if ($estudiante && !$estudiante->usuario) {
                $estudianteService = app(\App\Services\EstudianteService::class);
                $estudianteService->crearUsuarioParaEstudiante($estudiante);
                \Illuminate\Support\Facades\Log::info('Usuario creado automáticamente al matricular estudiante antiguo', ['estudiante_id' => $estudiante->id]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al asegurar usuario en matrícula', ['error' => $e->getMessage()]);
        }

        return $matricula;
    }
    
    /**
     * Actualiza una matrícula existente con validaciones.
     *
     * @param int $matriculaId
     * @param array $data
     * @return Matricula
     * @throws ValidationException
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
    
    /**
     * Valida si un estudiante puede matricularse en un horario específico.
     *
     * @param int $estudianteId
     * @param int $horarioId
     * @param string|TipoMatricula $tipoMatricula
     * @param int|null $cursoId
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validarMatricula(
        int $estudianteId,
        int $horarioId,
        string|TipoMatricula $tipoMatricula,
        ?int $cursoId = null
    ): array {
        $errores = [];

        // 1. Validar que el estudiante existe
        $estudiante = Estudiante::find($estudianteId);
        if (!$estudiante) {
            $errores[] = 'El estudiante no existe.';
            return ['valido' => false, 'errores' => $errores];
        }

        // 2. Validar que el horario existe
        $horario = Horario::find($horarioId);
        if (!$horario) {
            $errores[] = 'El horario no existe.';
            return ['valido' => false, 'errores' => $errores];
        }

        // 3. Validar vacantes disponibles
        if (!$this->tieneVacantesDisponibles($horarioId)) {
            $errores[] = 'No hay vacantes disponibles en este horario.';
        }

        // 4. Validar matrícula duplicada según tipo
        if ($this->tieneMatriculaDuplicada($estudianteId, $horarioId, $tipoMatricula, $cursoId)) {
            if ($tipoMatricula === TipoMatricula::CURSO) {
                $errores[] = 'El estudiante ya está matriculado en este curso.';
            } else {
                $errores[] = 'El estudiante ya está matriculado en este programa o formación continua.';
            }
        }

        // 5. Validar requisitos de módulos (si es tipo MODULO)
        if ($tipoMatricula === TipoMatricula::MODULO && $cursoId) {
            $validacionModulo = $this->validarRequisitosModulo($estudianteId, $cursoId, $horario->id_programa);
            if (!$validacionModulo['valido']) {
                $errores = array_merge($errores, $validacionModulo['errores']);
            }
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
        ];
    }

    /**
     * Verifica si hay vacantes disponibles en un horario.
     *
     * @param int $horarioId
     * @return bool
     */
    public function tieneVacantesDisponibles(int $horarioId): bool
    {
        $horario = Horario::find($horarioId);
        
        if (!$horario) {
            return false;
        }

        $matriculados = Matricula::where('horario_id', $horarioId)
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->count();

        return $matriculados < $horario->vacantes;
    }

    /**
     * Verifica si un estudiante ya tiene una matrícula activa.
     * 
     * La lógica de duplicado varía según el tipo:
     * - PROGRAMA/FORMACION_CONTINUA: Duplicado si existe una del mismo tipo en el mismo horario
     * - CURSO: Duplicado solo si existe una para el mismo curso específico
     * - MODULO: Duplicado solo si existe una para el mismo módulo específico
     * - UNIDAD: Duplicado solo si existe una para la misma unidad específica (permite cualquier orden)
     *
     * @param int $estudianteId
     * @param int $horarioId
     * @param TipoMatricula|null $tipoMatricula
     * @param int|null $cursoId
     * @param int|null $matriculaIdIgnorar ID de matrícula a ignorar (para edición)
     * @param int|null $unidadId ID de la unidad (para tipo UNIDAD)
     * @return bool
     */
    public function tieneMatriculaDuplicada(
        int $estudianteId, 
        int $horarioId,
        ?TipoMatricula $tipoMatricula = null,
        ?int $cursoId = null,
        ?int $matriculaIdIgnorar = null,
        ?int $unidadId = null
    ): bool {
        $query = Matricula::where('estudiante_id', $estudianteId)
            ->where('estado', '!=', EstadoMatricula::ANULADO);
        
        // Ignorar matrícula específica (para edición)
        if ($matriculaIdIgnorar) {
            $query->where('id', '!=', $matriculaIdIgnorar);
        }
        
        // Lógica diferenciada según tipo de matrícula
        if ($tipoMatricula === TipoMatricula::CURSO && $cursoId) {
            // Para CURSO: solo es duplicado si es el mismo curso exacto
            return $query
                ->where('id_curso', $cursoId)
                ->where('tipo_matricula', TipoMatricula::CURSO->value)
                ->exists();
        }
        
        // MODULO se comporta igual que CURSO: permite múltiples módulos diferentes
        if ($tipoMatricula === TipoMatricula::MODULO && $cursoId) {
            // Para MODULO: solo es duplicado si es el mismo módulo exacto
            return $query
                ->where('id_curso', $cursoId)
                ->where('tipo_matricula', TipoMatricula::MODULO->value)
                ->exists();
        }
        
        // UNIDAD: permite matricularse en cualquier unidad sin restricción de orden
        // Solo es duplicado si ya existe matrícula en la misma unidad exacta
        if ($tipoMatricula === TipoMatricula::UNIDAD && $unidadId) {
            return $query
                ->where('id_unidad', $unidadId)
                ->where('tipo_matricula', TipoMatricula::UNIDAD->value)
                ->exists();
        }
        
        if ($tipoMatricula === TipoMatricula::PROGRAMA || $tipoMatricula === TipoMatricula::FORMACION_CONTINUA) {
            // Para PROGRAMA/FORMACION_CONTINUA: solo una por horario del mismo tipo
            return $query
                ->where('horario_id', $horarioId)
                ->whereIn('tipo_matricula', [TipoMatricula::PROGRAMA->value, TipoMatricula::FORMACION_CONTINUA->value])
                ->exists();
        }
        
        // Fallback: comportamiento original (para compatibilidad)
        return $query
            ->where('horario_id', $horarioId)
            ->exists();
    }

    /**
     * Valida los requisitos previos para matricularse en un módulo.
     * 
     * Por ejemplo, verifica que el estudiante haya completado módulos anteriores
     * si el programa tiene una secuencia obligatoria.
     *
     * @param int $estudianteId
     * @param int $cursoId (módulo actual)
     * @param int $programaId
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validarRequisitosModulo(int $estudianteId, int $cursoId, int $programaId): array
    {
        $errores = [];

        // Obtener el curso/módulo actual
        $cursoActual = Curso::find($cursoId);
        if (!$cursoActual) {
            $errores[] = 'El módulo seleccionado no existe.';
            return ['valido' => false, 'errores' => $errores];
        }

        // Obtener todos los cursos/módulos del programa ordenados
        $cursos = Curso::where('id_programa', $programaId)
            ->orderBy('nombre_curso')
            ->get();

        // Encontrar la posición del curso actual
        $posicionActual = $cursos->search(function ($curso) use ($cursoId) {
            return $curso->id_curso === $cursoId;
        });

        // Si es el primer módulo, no hay requisitos
        if ($posicionActual === 0) {
            return ['valido' => true, 'errores' => []];
        }

        // Verificar que haya completado todos los módulos anteriores
        for ($i = 0; $i < $posicionActual; $i++) {
            $cursoAnterior = $cursos[$i];
            
            $matriculaAnterior = Matricula::where('estudiante_id', $estudianteId)
                ->where('id_curso', $cursoAnterior->id_curso)
                ->where('tipo_matricula', TipoMatricula::MODULO)
                ->where('estado', EstadoMatricula::CULMINADO)
                ->exists();

            if (!$matriculaAnterior) {
                $errores[] = "Debe completar el módulo '{$cursoAnterior->nombre_curso}' antes de matricularse en '{$cursoActual->nombre_curso}'.";
            }
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
        ];
    }

    /**
     * Anula una matrícula con validaciones de negocio.
     *
     * @param int $matriculaId
     * @param string $motivo
     * @param int|null $usuarioId Usuario que realiza la anulación
     * @return Matricula
     * @throws ValidationException
     */
    public function anularMatricula(int $matriculaId, string $motivo, ?int $usuarioId = null): Matricula
    {
        $matricula = Matricula::find($matriculaId);

        if (!$matricula) {
            throw ValidationException::withMessages([
                'matricula' => 'La matrícula no existe.',
            ]);
        }

        // No permitir anular una matrícula ya anulada
        if ($matricula->estado === EstadoMatricula::ANULADO) {
            throw ValidationException::withMessages([
                'estado' => 'Esta matrícula ya está anulada.',
            ]);
        }

        DB::beginTransaction();
        try {
            // Actualizar la matrícula
            $matricula->update([
                'estado' => EstadoMatricula::ANULADO,
                'motivo_anulacion' => $motivo,
                'fecha_anulacion' => now(),
            ]);

            // Anular los pagos pendientes del cronograma
            if ($matricula->cronograma) {
                $matricula->cronograma->pagos()
                    ->whereRaw("LOWER(estado) LIKE '%pendiente%'")
                    ->update(['estado' => 'Anulado']);
            }

            DB::commit();

            return $matricula->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cambia un estudiante de una sección/horario a otra.
     *
     * @param int $matriculaId
     * @param int $nuevoHorarioId
     * @param string $motivo
     * @return Matricula
     * @throws ValidationException
     */
    public function cambiarSeccion(int $matriculaId, int $nuevoHorarioId, string $motivo): Matricula
    {
        $matricula = Matricula::with('cronograma')->find($matriculaId);

        if (!$matricula) {
            throw ValidationException::withMessages([
                'matricula' => 'La matrícula no existe.',
            ]);
        }

        // No permitir cambio si está anulada o culminada
        if ($matricula->estado === EstadoMatricula::ANULADO) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede cambiar de sección una matrícula anulada.',
            ]);
        }

        if ($matricula->estado === EstadoMatricula::CULMINADO) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede cambiar de sección una matrícula culminada.',
            ]);
        }

        // Validar que haya vacantes en el nuevo horario
        if (!$this->tieneVacantesDisponibles($nuevoHorarioId)) {
            throw ValidationException::withMessages([
                'horario' => 'No hay vacantes disponibles en el horario destino.',
            ]);
        }

        // Validar que el nuevo horario sea del mismo programa
        $nuevoHorario = Horario::find($nuevoHorarioId);
        $horarioActual = Horario::find($matricula->horario_id);

        if ($nuevoHorario->id_programa !== $horarioActual->id_programa) {
            throw ValidationException::withMessages([
                'horario' => 'El nuevo horario debe ser del mismo programa.',
            ]);
        }

        DB::beginTransaction();
        try {
            // Cambiar el horario
            $matricula->update([
                'horario_id' => $nuevoHorarioId,
            ]);

            // Nota: El cronograma de pagos se mantiene igual ya que es el mismo programa
            // Si se requiere ajustar el cronograma, hacerlo aquí

            DB::commit();

            return $matricula->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene las vacantes disponibles de un horario.
     *
     * @param int $horarioId
     * @return array ['total' => int, 'ocupadas' => int, 'disponibles' => int]
     */
    public function obtenerVacantes(int $horarioId): array
    {
        $horario = Horario::find($horarioId);

        if (!$horario) {
            return ['total' => 0, 'ocupadas' => 0, 'disponibles' => 0];
        }

        $ocupadas = Matricula::where('horario_id', $horarioId)
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->count();

        return [
            'total' => $horario->vacantes,
            'ocupadas' => $ocupadas,
            'disponibles' => max(0, $horario->vacantes - $ocupadas),
        ];
    }

    /**
     * Obtiene el historial de matrículas de un estudiante.
     *
     * @param int $estudianteId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerHistorialMatriculas(int $estudianteId)
    {
        return Matricula::with(['horario.programa', 'curso', 'cronograma.pagos'])
            ->where('estudiante_id', $estudianteId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Verifica si un estudiante está matriculado en algún programa activo.
     *
     * @param int $estudianteId
     * @return bool
     */
    public function tieneMatriculaActiva(int $estudianteId): bool
    {
        return Matricula::where('estudiante_id', $estudianteId)
            ->whereIn('estado', [EstadoMatricula::ENPROCESO])
            ->exists();
    }
}
