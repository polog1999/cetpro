<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Unidad;

use App\Models\Curso;
use App\Models\Cronograma;
use App\Enums\EstadoMatricula;
use App\Enums\TipoMatricula;
use App\Enums\TipoCertificado;

class Matricula extends Model
{
    use HasFactory;

    protected $table = 'matriculas';

    /**
     * Flag to skip automatic cronograma generation.
     * Set to true when importing legacy students.
     */
    public bool $skipCronogramaGeneration = false;

    protected $fillable = [
        'codigo_inscripcion',
        'estudiante_id',
        'horario_id',
        'estado',
        'tipo_matricula',
        'id_curso',
        'id_unidad',
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

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'id_unidad', 'id_unidad');
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
        $numCuotas    = null;
        $especialidad = null;

        // 1) CURSO / UNIDAD -> PAGO ÚNICO (Total por el curso/unidad)
        if (in_array($this->tipo_matricula, [TipoMatricula::CURSO, TipoMatricula::UNIDAD], true)) {
            $curso = $this->curso;

            if (! $curso) {
                return null; // no hay curso asociado
            }

            $numCuotas    = 1;
            $especialidad = $curso->programa?->especialidad;
        }

        // 2) MODULO (Formación Continua) -> PAGOS MENSUALES según duración del módulo
        if ($this->tipo_matricula === TipoMatricula::MODULO) {
            $curso = $this->curso;

            if (! $curso) {
                return null;
            }

            // Usar la duración del módulo (en meses) como número de cuotas
            $numCuotas    = max(1, (int) $curso->duracion);
            $especialidad = $curso->programa?->especialidad;
        }

        // 3) PROGRAMA o FORMACION_CONTINUA -> DURACIÓN DEL PROGRAMA (Pagos mensuales)
        if (in_array($this->tipo_matricula, [TipoMatricula::PROGRAMA, TipoMatricula::FORMACION_CONTINUA], true)) {
            $programa = $this->horario?->programa;

            if (! $programa) {
                return null;
            }

            $duracion     = $programa->duracion;     // Duración global en meses
            $numCuotas    = (int) $duracion;         // 1 cuota por mes
            
            // Restar meses transcurridos desde el inicio del programa
            $minFechaCurso = $programa->cursos()->min('fecha_inicio');
            $mesesTranscurridos = $this->calcularMesesTranscurridosParaDescuento($minFechaCurso);
            
            if ($mesesTranscurridos > 0) {
                // Dejamos al menos 1 cuota si ya se pasó toda la duración
                $numCuotas = max(1, $numCuotas - $mesesTranscurridos);
            }
            
            $especialidad = $programa->especialidad;
        }

        if (! $numCuotas || ! $especialidad) {
            return null;
        }

        // monto_total = costo_mensual * num_cuotas
        // Para cursos únicos, se asume costo mensual por ahora (o se ajustará lógica futura)
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
            
            if ($this->tipo_matricula === TipoMatricula::CURSO || 
                $this->tipo_matricula === TipoMatricula::MODULO ||
                $this->tipo_matricula === TipoMatricula::UNIDAD) {
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

        // CASO 1: Pago Único (Curso / Unidad)
        // Se genera una sola fecha basada en el inicio del curso o la fecha actual
        if (in_array($this->tipo_matricula, [TipoMatricula::CURSO, TipoMatricula::UNIDAD], true)) {
            $curso = $this->curso;

            if ($curso && $curso->fecha_inicio) {
                $fechas[] = Carbon::parse($curso->fecha_inicio)->endOfMonth();
            } else {
                $fechas[] = Carbon::today()->endOfMonth();
            }
        }
        // CASO 2: Módulo (Formación Continua) -> Mensualidades según fechas del módulo
        elseif ($this->tipo_matricula === TipoMatricula::MODULO) {
            $curso = $this->curso;
            $inicio = ($curso && $curso->fecha_inicio)
                ? Carbon::parse($curso->fecha_inicio)
                : Carbon::today();

            for ($i = 0; $i < $numCuotas; $i++) {
                $fechas[] = $inicio->copy()->addMonths($i)->endOfMonth();
            }
        }
        // CASO 3: Programa Completo (Mensualidades)
        // Se generan N fechas mensuales consecutivas desde el inicio del programa
        else {
            $programa = $this->horario?->programa;
            $inicio = Carbon::today(); // Default
            
            $mesesOffset = 0;

            // Obtener fecha de inicio real del programa (min fecha inicio de cursos)
            if ($programa) {
                $minFechaCurso = $programa->cursos()->min('fecha_inicio');
                if ($minFechaCurso) {
                    $inicio = Carbon::parse($minFechaCurso);
                    
                    // Solo aplicar el offset de meses transcurridos si es Programa o Formacion Continua
                    if (in_array($this->tipo_matricula, [TipoMatricula::PROGRAMA, TipoMatricula::FORMACION_CONTINUA], true)) {
                        $mesesOffset = $this->calcularMesesTranscurridosParaDescuento($minFechaCurso);
                    }
                }
            }

            // Generar cuotas mensuales consecutivas
            for ($i = 0; $i < $numCuotas; $i++) {
                $fechas[] = $inicio->copy()->addMonths($i + $mesesOffset)->endOfMonth();
            }
        }

        // Fallback robusto por si acaso
        $actual = count($fechas);

        if ($actual < $numCuotas) {
            $ultima = count($fechas) > 0 ? end($fechas) : Carbon::today();
            $ultima = $ultima instanceof Carbon ? $ultima : Carbon::parse($ultima);

            for ($i = 1; $i <= $numCuotas - $actual; $i++) {
                $fechas[] = $ultima->copy()->addMonths($i)->endOfMonth();
            }
        } elseif ($actual > $numCuotas) {
            $fechas = array_slice($fechas, 0, $numCuotas);
        }

        return $fechas;
    }

    /**
     * Calcula los meses transcurridos desde el inicio del programa hasta hoy,
     * para descontar cuotas.
     */
    protected function calcularMesesTranscurridosParaDescuento(?string $fechaInicio): int
    {
        if (!$fechaInicio) {
            return 0;
        }
        
        $inicio = Carbon::parse($fechaInicio);
        $hoy = Carbon::today();
        
        if ($hoy->format('Y-m') >= $inicio->format('Y-m')) {
            $diffMeses = ($hoy->year - $inicio->year) * 12 + ($hoy->month - $inicio->month);
            
            // Si se ha pasado el día 5 del mes presente, se considera que ese mes también transcurrió
            $meses = ($hoy->day > 5) ? $diffMeses + 1 : $diffMeses;
            return max(0, $meses);
        }
        
        return 0;
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

        // Usar el servicio centralizado para validar duplicados
        $service = app(\App\Services\MatriculaService::class);
        
        $resultado = $service->validarDuplicado(
            $matricula->estudiante_id,
            $matricula->horario_id,
            null, // matriculaIdIgnorar (es creación, no edición)
            $matricula->tipo_matricula,
            $matricula->id_curso,
            $matricula->id_unidad
        );
        
        if (!$resultado['valido']) {
            // Determinar el campo correcto para el mensaje de error según el tipo
            $campo = match ($matricula->tipo_matricula) {
                TipoMatricula::UNIDAD => 'id_unidad',
                TipoMatricula::CURSO, TipoMatricula::MODULO => 'id_curso',
                default => 'horario_id',
            };
            
            throw \Illuminate\Validation\ValidationException::withMessages([
                $campo => $resultado['mensaje'],
            ]);
        }
        
        // Validaciones de integridad (campos requeridos)
        self::validarIntegridadSegunTipo($matricula);
    }

    /**
     * Valida que los campos requeridos estén presentes según el tipo de matrícula.
     *
     * @throws \Illuminate\Validation\ValidationException Si faltan campos requeridos
     */
    private static function validarIntegridadSegunTipo(Matricula $matricula): void
    {
        if (in_array($matricula->tipo_matricula, [TipoMatricula::CURSO, TipoMatricula::MODULO])) {
            if (!$matricula->id_curso) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'id_curso' => 'Debe seleccionar un curso para matrículas de tipo CURSO/MODULO.',
                ]);
            }
        }
        
        if ($matricula->tipo_matricula === TipoMatricula::UNIDAD) {
            if (!$matricula->id_curso || !$matricula->id_unidad) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'id_unidad' => 'Debe seleccionar un curso y una unidad.',
                ]);
            }
        }
    }

    /**
     * Genera el código de inscripción para la matrícula.
     * 
     * Delega la lógica al servicio para evitar duplicidad.
     */
    private static function generarCodigoInscripcion(Matricula $matricula, ?Horario $horario): void
    {
        if (!empty($matricula->codigo_inscripcion)) {
            return;
        }

        // Delegar al servicio centralizado
        // Pasamos 0 como horarioId por defecto si no hay, solo para cumplir la firma del método
        $service = app(\App\Services\MatriculaService::class);
        $matricula->codigo_inscripcion = $service->generarCodigoInscripcion(
            $horario?->id_horario ?? 0
        );
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
            // No generar cronograma si es importación de alumno antiguo
            if ($matricula->skipCronogramaGeneration) {
                return;
            }
            $matricula->generarCronograma();
        });
    }
}
