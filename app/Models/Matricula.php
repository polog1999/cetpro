<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

use App\Models\Estudiante;
use App\Models\Horario;

use App\Models\Curso;
use App\Models\Cronograma;
use App\Enums\EstadoMatricula;
use App\Enums\TipoMatricula;
use App\Enums\TipoCertificado;

class Matricula extends Model
{
    use HasFactory;

    protected $table = 'matriculas';

    protected $fillable = [
        'codigo_inscripcion',
        'estudiante_id',
        'horario_id',
        'estado',
        'tipo_matricula',
        'id_curso',
        'motivo_anulacion',
        'fecha_anulacion',
        'documento_path',
        'tipo_certificado',
    ];

    protected $casts = [
        'estado'           => EstadoMatricula::class,
        'tipo_matricula'   => TipoMatricula::class,
        'tipo_certificado' => TipoCertificado::class,
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function horario(): BelongsTo
{
    return $this->belongsTo(Horario::class, 'horario_id', 'id_horario');
}

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'id_curso', 'id_curso');
    }

    public function cronograma(): HasOne
    {
        return $this->hasOne(Cronograma::class);
    }

    /**
     * Relación con Notas
     */
    public function notas()
    {
        return $this->hasMany(Nota::class);
    }

    /**
     * Obtener notas agrupadas por curso
     */
    public function obtenerNotasPorCurso($cursoId)
    {
        return $this->notas()->where('curso_id', $cursoId)->get();
    }

    /**
     * Genera y guarda el cronograma de pagos de esta matrícula
     * y sus cuotas (pagos) correspondientes.
     */
    public function generarCronograma(): ?Cronograma
    {
        $duracion     = null;
        $especialidad = null;

        // 1) CURSO -> duración del curso
        if (in_array($this->tipo_matricula, [TipoMatricula::CURSO, TipoMatricula::MODULO], true)) {
            $curso = $this->curso;

            if (! $curso) {
                return null; // no hay curso asociado
            }

            $duracion     = $curso->duracion;                // p.ej. en meses o nº de cuotas
            $especialidad = $curso->programa?->especialidad; // programa del curso -> especialidad
        }

        // 2) PROGRAMA o FORMACION_CONTINUA -> duración del programa
        if (in_array($this->tipo_matricula, [TipoMatricula::PROGRAMA, TipoMatricula::FORMACION_CONTINUA], true)) {
            $programa = $this->horario?->programa;

            if (! $programa) {
                return null;
            }

            $duracion     = $programa->duracion;     // p.ej. en meses o nº de cuotas
            $especialidad = $programa->especialidad;
        }

        if (! $duracion || ! $especialidad) {
            return null;
        }

        // num_cuotas = duración
        $numCuotas  = (int) $duracion;

        // monto_total = costo_mensual * duración
        $montoTotal = $numCuotas * $especialidad->costo_mensual;

        // Crear cronograma
        $cronograma = $this->cronograma()->create([
            'num_cuotas'  => $numCuotas,
            'monto_total' => $montoTotal,
        ]);

        // Generar pagos/cuotas para este cronograma
        $this->generarPagosParaCronograma($cronograma);

        return $cronograma;
    }

    /**
     * Genera los pagos (cuotas) para un cronograma dado:
     * - nro_cuota: 1..num_cuotas
     * - monto: monto_total / num_cuotas (ajustando el último por redondeo)
     * - estado: pendiente
     * - fecha_vencimiento: fin de cada mes según la lógica de cursos/programa
     * - num_liquidacion: código generado desde Oracle (si aplica)
     */
    protected function generarPagosParaCronograma(Cronograma $cronograma): void
    {
        $numCuotas = (int) $cronograma->num_cuotas;

        if ($numCuotas <= 0) {
            return;
        }

        $montoTotal = (float) $cronograma->monto_total;

        // Monto base por cuota
        $montoPorCuota = round($montoTotal / $numCuotas, 2);

        // Arreglo con los montos de cada cuota
        $montos = array_fill(0, $numCuotas, $montoPorCuota);

        // Ajuste por centavos de redondeo para que la suma final sea exacta
        $suma   = $montoPorCuota * $numCuotas;
        $diff   = round($montoTotal - $suma, 2);

        if ($diff !== 0.0) {
            $montos[$numCuotas - 1] += $diff;
        }

        // ========================================
        // NUEVO: Preparar datos para liquidación Oracle
        // ========================================
        $codigoContribuyente = null;
        $codigoEspecialidad = null;
        $oracleService = null;
        
        try {
            // 1. Obtener código de contribuyente del estudiante
            // IMPORTANTE: Usamos verificarContribuyenteExistente() que busca en VU_CETPRO_BUS
            // donde sí existen los contribuyentes recién creados, en lugar de
            // obtenerCodigoContribuyenteMasReciente() que busca en VU_BUSCA_TUSNE_PER_Pen
            // (vista de liquidaciones pendientes, vacía para nuevos contribuyentes)
            $oracleService = app(\App\Services\OracleTusneService::class);
            $estudiante = $this->estudiante;
            
            if ($estudiante && $estudiante->nro_documento) {
                // Buscar el código de contribuyente en VU_CETPRO_BUS
                $codigoContribuyente = $oracleService->verificarContribuyenteExistente($estudiante->nro_documento);
            }
            
            // 2. Obtener especialidad y mapear a código B000X
            $especialidad = null;
            
            if ($this->tipo_matricula === TipoMatricula::CURSO) {
                $especialidad = $this->curso?->programa?->especialidad;
            } else {
                $especialidad = $this->horario?->programa?->especialidad;
            }
            
            if ($especialidad && $especialidad->nombre_especialidad) {
                $codigoEspecialidad = $this->obtenerCodigoEspecialidad($especialidad->nombre_especialidad);
            }
            
        } catch (\Exception $e) {
            \Log::warning('No se pudo obtener datos para liquidación Oracle: ' . $e->getMessage(), [
                'matricula_id' => $this->id,
                'estudiante_id' => $this->estudiante_id,
            ]);
            // Continuar sin códigos de liquidación
        }
        
        // DEBUG: Log para verificar los datos obtenidos para liquidación
        \Log::info('Datos para generar liquidación', [
            'matricula_id' => $this->id,
            'estudiante_id' => $this->estudiante_id,
            'nro_documento' => $this->estudiante?->nro_documento,
            'codigo_contribuyente' => $codigoContribuyente,
            'especialidad_nombre' => $this->tipo_matricula === TipoMatricula::CURSO 
                ? $this->curso?->programa?->especialidad?->nombre_especialidad
                : $this->horario?->programa?->especialidad?->nombre_especialidad,
            'codigo_especialidad' => $codigoEspecialidad,
            'puede_generar_liquidacion' => $codigoContribuyente && $codigoEspecialidad && $oracleService ? 'SI' : 'NO',
        ]);

        // Fechas de vencimiento para cada cuota
        $fechasVencimiento = $this->calcularFechasVencimientoCuotas($numCuotas);

        // ========================================
        // Crear pagos con códigos de liquidación
        // ========================================
        for ($i = 1; $i <= $numCuotas; $i++) {
            $numLiquidacion = null;
            $fechaLiquidacion = null;
            
            // Generar código de liquidación si tenemos todos los datos necesarios
            if ($codigoContribuyente && $codigoEspecialidad && $oracleService) {
                try {
                    $numLiquidacion = $oracleService->generarCodigoLiquidacion(
                        $codigoEspecialidad,
                        $codigoContribuyente
                    );
                    
                    if ($numLiquidacion) {
                        $fechaLiquidacion = now();
                    }
                } catch (\Exception $e) {
                    \Log::error("Error generando código de liquidación para cuota {$i}: " . $e->getMessage(), [
                        'matricula_id' => $this->id,
                        'cronograma_id' => $cronograma->id,
                        'nro_cuota' => $i,
                    ]);
                    // Continuar sin código de liquidación para esta cuota
                }
            }
            
            // Obtener estado desde Oracle si tenemos número de liquidación
            $estadoOracle = 'Pendiente'; // Default si no se puede obtener
            if ($numLiquidacion && $oracleService) {
                try {
                    $estadoOracle = $oracleService->obtenerEstadoLiquidacion($numLiquidacion) ?? 'Pendiente';
                } catch (\Exception $e) {
                    \Log::warning("Error obteniendo estado de liquidación: " . $e->getMessage());
                }
            }
            
            // Crear el pago con código de liquidación y estado desde Oracle
            $cronograma->pagos()->create([
                'nro_cuota'         => $i,
                // 'codigo' se genera en el modelo Pago::creating si se deja null
                'monto'             => $montos[$i - 1],
                'estado'            => $estadoOracle,  // ← Estado desde Oracle
                'fecha_vencimiento' => $fechasVencimiento[$i - 1] ?? null,
                'metodo_pago'       => null,
                'fecha_pago'        => null,
                'evidencia_path'    => null,
                'num_liquidacion'   => $numLiquidacion,      // ← Código generado desde Oracle
                'fecha_liquidacion' => $fechaLiquidacion,    // ← Fecha de generación
            ]);
        }
    }

    /**
     * Obtiene el código de especialidad para Oracle según el nombre.
     * 
     * Mapea nombres de especialidades a códigos B000X requeridos por
     * la función Oracle fu_digito_generar.
     *
     * @param string|null $nombreEspecialidad Nombre de la especialidad
     * @return string|null Código B000X o null si no coincide
     */
    protected function obtenerCodigoEspecialidad(?string $nombreEspecialidad): ?string
    {
        if (!$nombreEspecialidad) {
            return null;
        }
        
        // Normalizar nombre (quitar espacios, convertir a minúsculas)
        $nombreNormalizado = strtolower(trim($nombreEspecialidad));
        
        // Mapeo de nombres a códigos Oracle
        // IMPORTANTE: Agregar todas las especialidades de tu CETPRO con su código B000X correspondiente
        $mapeo = [
            // Estética Personal
            'estética personal' => 'B0001',
            'estetica personal' => 'B0001',
            
            // Confección Textil
            'confección textil' => 'B0002',
            'confeccion textil' => 'B0002',
            
            // Ofimática / Computación
            'ofimática' => 'B0003',
            'ofimatica' => 'B0003',
            'computación e informática' => 'B0003',
            'computacion e informatica' => 'B0003',
            'computación' => 'B0003',
            'computacion' => 'B0003',
            'informática' => 'B0003',
            'informatica' => 'B0003',
        ];
        
        return $mapeo[$nombreNormalizado] ?? null;
    }

    /**
     * Calcula la fecha de vencimiento de cada cuota:
     *
     * - Siempre fin de mes.
     * - Para PROG_ESTUDIO / FORM_CONTINUA:
     *      se toman las fechas de inicio de los cursos del programa,
     *      ordenados por fecha_inicio, y se usa fin de ese mes.
     * - Para CURSO_LIBRE:
     *      se toma la fecha_inicio del curso y se usa fin de ese mes.
     * - Si faltan fechas, se continúa sumando meses desde la última.
     * - Si no hay fechas de cursos, se usan meses desde hoy como fallback.
     */
    protected function calcularFechasVencimientoCuotas(int $numCuotas): array
    {
        $fechas = [];

        // Caso CURSO LIBRE: generar fechas según la duración del curso
        if ($this->tipo_matricula === TipoMatricula::CURSO) {
            $curso = $this->curso;

            if ($curso && $curso->fecha_inicio) {
                $inicio = Carbon::parse($curso->fecha_inicio);
                $duracion = (int) $curso->duracion; // duración en meses
                
                // Generar una fecha de vencimiento por cada mes del curso
                for ($i = 0; $i < $duracion; $i++) {
                    $fechas[] = $inicio->copy()->addMonths($i)->endOfMonth();
                }
            }
        } else {
            // Programa de estudio / formación continua
            $programa = $this->horario?->programa;

            if ($programa) {
                $cursos = $programa->cursos()
                    ->orderBy('fecha_inicio')
                    ->get();

                // Para cada curso, generar tantas fechas como su duración indique
                foreach ($cursos as $curso) {
                    if ($curso->fecha_inicio) {
                        $inicio = Carbon::parse($curso->fecha_inicio);
                        $duracion = (int) $curso->duracion; // duración en meses
                        
                        // Generar una fecha de vencimiento por cada mes del curso
                        for ($i = 0; $i < $duracion; $i++) {
                            $fechas[] = $inicio->copy()->addMonths($i)->endOfMonth();
                        }
                    }
                }
            }
        }

        $actual = count($fechas);

        // Sin fechas de cursos => fallback: usar meses desde hoy
        if ($actual === 0) {
            $inicio = Carbon::today();

            for ($i = 0; $i < $numCuotas; $i++) {
                $fechas[] = $inicio->copy()->addMonths($i)->endOfMonth();
            }
        }
        // Si hay menos fechas que cuotas, completar meses desde la última fecha
        elseif ($actual < $numCuotas) {
            $ultima = end($fechas);
            $ultima = $ultima instanceof Carbon ? $ultima : Carbon::parse($ultima);

            for ($i = 1; $i <= $numCuotas - $actual; $i++) {
                $fechas[] = $ultima->copy()->addMonths($i)->endOfMonth();
            }
        }
        // Si hay más fechas que cuotas, nos quedamos con las primeras
        elseif ($actual > $numCuotas) {
            $fechas = array_slice($fechas, 0, $numCuotas);
        }

        return $fechas;
    }

    /**
     * Actualiza el estado de la matrícula según el estado del cronograma.
     * Si el cronograma tiene deudas, marca la matrícula como INTERRUMPIDO.
     * Si no tiene deudas, la marca como EN PROCESO (a menos que ya esté CULMINADO).
     */
    public function actualizarEstadoSegunCronograma(): void
    {
        // Si no tiene cronograma, no hacer nada
        if (!$this->cronograma) {
            return;
        }

        // No modificar matrículas ya culminadas
        if ($this->estado === EstadoMatricula::CULMINADO) {
            return;
        }

        // Verificar si el cronograma tiene deudas
        if ($this->cronograma->tieneDeuda()) {
            // Tiene deudas -> INTERRUMPIDO
            $this->estado = EstadoMatricula::INTERRUMPIDO;
        } else {
            // No tiene deudas -> EN PROCESO
            $this->estado = EstadoMatricula::ENPROCESO;
        }

        // Guardar sin disparar eventos para evitar loops infinitos
        $this->saveQuietly();
    }

    /**
     * Valida que existan vacantes disponibles en el horario.
     *
     * @throws \Illuminate\Validation\ValidationException Si no hay vacantes disponibles
     */
    private static function validarVacantes(Matricula $matricula, ?Horario $horario): void
    {
        if (!$horario) {
            return;
        }

        $matriculados = Matricula::where('horario_id', $matricula->horario_id)
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->count();
        
        if ($matriculados >= $horario->vacantes) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'horario_id' => 'No hay vacantes disponibles en este horario.',
            ]);
        }
    }

    /**
     * Valida que no exista una matrícula duplicada para el estudiante.
     * 
     * Para PROGRAMA/FORMACION_CONTINUA: no permite otra del mismo tipo en el mismo horario.
     * Para CURSO: valida que id_curso esté presente y no permite otra matrícula en el mismo curso.
     *
     * @throws \Illuminate\Validation\ValidationException Si ya existe una matrícula duplicada
     */
    private static function validarDuplicado(Matricula $matricula): void
    {
        if (!$matricula->estudiante_id || !$matricula->horario_id) {
            return;
        }

        if ($matricula->tipo_matricula === TipoMatricula::PROGRAMA || 
            $matricula->tipo_matricula === TipoMatricula::FORMACION_CONTINUA) {
            self::validarDuplicadoPrograma($matricula);
        } elseif ($matricula->tipo_matricula === TipoMatricula::CURSO) {
            self::validarIntegridadCurso($matricula);
            self::validarDuplicadoCurso($matricula);
        }
    }

    /**
     * Valida que no exista una matrícula duplicada para PROGRAMA o FORMACION_CONTINUA.
     *
     * @throws \Illuminate\Validation\ValidationException Si el estudiante ya está matriculado
     */
    private static function validarDuplicadoPrograma(Matricula $matricula): void
    {
        $exists = Matricula::where('estudiante_id', $matricula->estudiante_id)
            ->where('horario_id', $matricula->horario_id)
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->whereIn('tipo_matricula', [TipoMatricula::PROGRAMA->value, TipoMatricula::FORMACION_CONTINUA->value])
            ->exists();
        
        if ($exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'horario_id' => 'El estudiante ya está matriculado en este programa o formación continua.',
            ]);
        }
    }

    /**
     * Valida la integridad de datos para matrículas de tipo CURSO.
     * Asegura que id_curso siempre exista cuando tipo_matricula es CURSO.
     *
     * @throws \Illuminate\Validation\ValidationException Si id_curso no está presente
     */
    private static function validarIntegridadCurso(Matricula $matricula): void
    {
        if (!$matricula->id_curso) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_curso' => 'Debe seleccionar un curso para matrículas de tipo CURSO.',
            ]);
        }
    }

    /**
     * Valida que no exista una matrícula duplicada para el mismo curso.
     * 
     * Solo bloquea si ya existe una matrícula de tipo CURSO para el mismo curso exacto.
     * Permite múltiples matrículas de tipo CURSO en diferentes cursos del mismo programa.
     *
     * @throws \Illuminate\Validation\ValidationException Si el estudiante ya está matriculado en el curso
     */
    private static function validarDuplicadoCurso(Matricula $matricula): void
    {
        $exists = Matricula::where('estudiante_id', $matricula->estudiante_id)
            ->where('id_curso', $matricula->id_curso)
            ->where('tipo_matricula', TipoMatricula::CURSO->value)
            ->where('estado', '!=', EstadoMatricula::ANULADO)
            ->exists();
        
        if ($exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_curso' => 'El estudiante ya está matriculado en este curso.',
            ]);
        }
    }

    /**
     * Genera el código de inscripción para la matrícula.
     * 
     * Formato: AñoDNIHorarioID (sin guiones)
     * Ejemplo: 2026123456781 (año 2026 + DNI 12345678 + horario ID 1)
     */
    private static function generarCodigoInscripcion(Matricula $matricula, ?Horario $horario): void
    {
        if (!empty($matricula->codigo_inscripcion)) {
            return;
        }

        $year = now()->format('Y');
        
        // Obtener DNI del estudiante
        $estudiante = Estudiante::find($matricula->estudiante_id);
        $dni = $estudiante?->nro_documento ?? '00000000';
        
        // Obtener ID del horario
        $horarioId = $horario?->id_horario ?? $matricula->horario_id ?? '0';
        
        // Formato: AñoDNIHorarioID (sin guiones)
        $matricula->codigo_inscripcion = "{$year}{$dni}{$horarioId}";
    }

    /**
     * Generación automática del código de inscripción y cronograma.
     */
    protected static function booted()
    {
        // ANTES de guardar: validar y generar código de inscripción
        static::creating(function (Matricula $matricula) {
            $horario = Horario::find($matricula->horario_id);
            
            // Paso 4: Validaciones de integridad
            self::validarVacantes($matricula, $horario);
            self::validarDuplicado($matricula);
            
            // Generar código de inscripción
            self::generarCodigoInscripcion($matricula, $horario);
        });
        
        // DESPUÉS de guardar: generar cronograma
        static::created(function (Matricula $matricula) {
            $matricula->generarCronograma();
        });
    }
}
