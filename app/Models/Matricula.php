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
use Illuminate\Support\Facades\DB;

class Matricula extends Model
{
    use HasFactory;

    protected $table = 'matriculas';

    /**
     * Flag to skip automatic cronograma generation.
     * Set to true when importing legacy students.
     */
    public bool $skipCronogramaGeneration = false;
    // En tu modelo Matricula.php, añade este atributo temporal:
    public ?bool $cobrar_mes_actual = null;
    public ?int $num_cuotas_personalizado = null; // 👈 Atributo temporal en memoria
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
        'cobrar_mes_actual', // 👈 Añadir aquí temporalmente
        'num_cuotas_personalizado', // 👈 Añadir aquí

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

        // 2) MODULO -> PAGOS MENSUALES según duración del módulo
        if ($this->tipo_matricula === TipoMatricula::MODULO) {
            $curso = $this->curso;

            if (! $curso) {
                return null;
            }

            // Usar la duración del módulo (en meses) como número de cuotas
            $numCuotas    = max(1, (int) $curso->duracion);
            $especialidad = $curso->programa?->especialidad;
        }

        // 2b) FORMACION_CONTINUA / PROGRAMA -> PAGOS MENSUALES según duración del programa
        //     Ambos tipos usan la misma lógica: duración del programa, especialidad del programa,
        //     y descuento de meses transcurridos desde la primera fecha de curso.
        if (in_array($this->tipo_matricula, [TipoMatricula::FORMACION_CONTINUA, TipoMatricula::PROGRAMA], true)) {
            $programa = $this->horario?->programa;

            if (! $programa) {
                return null;
            }

            // 1. Si el administrativo ingresó una duración personalizada, usamos esa
            if ($this->num_cuotas_personalizado !== null && $this->num_cuotas_personalizado > 0) {
                $numCuotas = $this->num_cuotas_personalizado;
            }
            // 2. Si no, calculamos automáticamente la duración por defecto del programa
            else {
                $duracion     = $programa->duracion;     // Duración global en meses
                $numCuotas    = (int) $duracion;         // 1 cuota por mes

                // Restar meses transcurridos desde el inicio del programa
                $minFechaCurso = $programa->cursos()->min('fecha_inicio');
                $mesesTranscurridos = $this->calcularMesesTranscurridosParaDescuento($minFechaCurso);

                if ($mesesTranscurridos > 0) {
                    $numCuotas = max(1, $numCuotas - $mesesTranscurridos);
                }
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

            if (
                $this->tipo_matricula === TipoMatricula::CURSO ||
                $this->tipo_matricula === TipoMatricula::MODULO ||
                $this->tipo_matricula === TipoMatricula::UNIDAD
            ) {
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
                    $oracleService->actualizarFechaVencimiento($fechasVencimiento[$i - 1], $numLiquidacion);
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
        // CASO 2: Módulo -> Mensualidades según fechas del módulo
        elseif ($this->tipo_matricula === TipoMatricula::MODULO) {
            $curso = $this->curso;
            $inicio = ($curso && $curso->fecha_inicio)
                ? Carbon::parse($curso->fecha_inicio)
                : Carbon::today();

            for ($i = 0; $i < $numCuotas; $i++) {
                $fechas[] = $inicio->copy()->addMonths($i)->endOfMonth();
            }
        }
        // CASO 2b/3: Formación Continua y Programa -> Mensualidades según duración del programa
        //     Ambos tipos comparten la misma lógica de fechas.
        else {
            $programa = $this->horario?->programa;
            $inicio = Carbon::today(); // Default

            $mesesOffset = 0;

            // Obtener fecha de inicio real del programa (min fecha inicio de cursos)
            if ($programa) {
                $minFechaCurso = $programa->cursos()->min('fecha_inicio');
                if ($minFechaCurso) {
                    $inicio = Carbon::parse($minFechaCurso);

                    // Aplicar el offset de meses transcurridos para ambos tipos
                    $mesesOffset = $this->calcularMesesTranscurridosParaDescuento($minFechaCurso);
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

            // NUEVO: Si el usuario especificó explícitamente si desea cobrar el mes actual
            if ($this->cobrar_mes_actual !== null) {
                // Si SÍ quiere cobrar el mes actual, descontamos solo los meses anteriores ($diffMeses)
                // Si NO quiere cobrar el mes actual, descontamos los anteriores + el actual ($diffMeses + 1)
                return $this->cobrar_mes_actual ? max(0, $diffMeses) : max(0, $diffMeses + 1);
            }

            // Fallback original si no se envía el parámetro (para importaciones, etc.)
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
     * NOTA: Deshabilitado — el aforo (vacantes) es solo informativo/formalismo.
     * Ya no bloquea la creación de matrículas.
     */
    private static function validarVacantes(Matricula $matricula, ?Horario $horario): void
    {
        // No-op: el aforo es solo un formalismo, no bloquea matrículas.
        return;
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

            // 1. NUEVO: Capturar cobrar_mes_actual y removerlo de los atributos de la base de datos
            if (array_key_exists('cobrar_mes_actual', $matricula->attributes)) {
                $matricula->cobrar_mes_actual = (bool) $matricula->attributes['cobrar_mes_actual'];
                unset($matricula->attributes['cobrar_mes_actual']); // 👈 Se remueve para que Postgres no dé error de columna inexistente
            }
            // 2. NUEVO: Capturar num_cuotas_personalizado
            if (array_key_exists('num_cuotas_personalizado', $matricula->attributes)) {
                $matricula->num_cuotas_personalizado = $matricula->attributes['num_cuotas_personalizado']
                    ? (int) $matricula->attributes['num_cuotas_personalizado']
                    : null;
                unset($matricula->attributes['num_cuotas_personalizado']); // Remover para evitar error en Postgres
            }
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
    /**
     * Extiende una matrícula existente agregando cuotas adicionales
     * a partir de una fecha específica (puede ser retroactiva).
     */
    /**
     * Extiende una matrícula existente agregando cuotas adicionales
     * y actualizando el tipo de matrícula a nivel académico si corresponde.
     */


    public function extenderMatricula(int $cuotasAdicionales, \Carbon\Carbon $fechaInicioExtension): ?Cronograma
    {
        $cronograma = $this->cronograma;

        if (!$cronograma) {
            return null;
        }

        // -------------------------------------------------------------
        // 1. Transición Automática de Tipo de Matrícula (Ascenso)
        // -------------------------------------------------------------
        if ($this->tipo_matricula === TipoMatricula::CURSO) {
            $this->update([
                'tipo_matricula' => TipoMatricula::FORMACION_CONTINUA,
                'id_curso'       => null,
            ]);
        } elseif (in_array($this->tipo_matricula, [TipoMatricula::MODULO, TipoMatricula::UNIDAD], true)) {
            $this->update([
                'tipo_matricula' => TipoMatricula::PROGRAMA,
                'id_curso'       => null,
                'id_unidad'      => null,
            ]);
        }

        // Determinar la especialidad según el estado de la matrícula
        $especialidad = null;
        if ($this->tipo_matricula === TipoMatricula::FORMACION_CONTINUA || $this->tipo_matricula === TipoMatricula::PROGRAMA) {
            $especialidad = $this->horario?->programa?->especialidad;
        } else {
            $especialidad = $this->curso?->programa?->especialidad;
        }

        if (!$especialidad) {
            throw new \Exception("No se pudo determinar la especialidad para calcular el costo.");
        }

        // -------------------------------------------------------------
        // 2. NUEVO: Filtrado y Validación de Duplicados por Mes-Año
        // -------------------------------------------------------------
        // Obtener los meses/años de las cuotas que ya existen (Formato: '2026-04')
        $mesesExistentes = $cronograma->pagos()
            ->whereNotNull('fecha_vencimiento')
            ->pluck('fecha_vencimiento')
            ->map(fn($fecha) => \Carbon\Carbon::parse($fecha)->format('Y-m'))
            ->toArray();

        $fechasNuevasEfectivas = [];

        // Evaluar cada mes propuesto dentro del rango de extensión solicitado
        for ($i = 0; $i < $cuotasAdicionales; $i++) {
            $fechaCandidata = $fechaInicioExtension->copy()->addMonths($i)->endOfMonth();
            $mesAnioCandidato = $fechaCandidata->format('Y-m');

            // Si el mes NO existe en el cronograma actual, lo agregamos como nueva cuota válida
            if (!in_array($mesAnioCandidato, $mesesExistentes, true)) {
                $fechasNuevasEfectivas[] = $fechaCandidata;
            }
        }

        // Si todas las cuotas solicitadas ya existen, lanzamos una excepción para avisar al usuario
        if (empty($fechasNuevasEfectivas)) {
            throw new \Exception("Todas las cuotas dentro del rango seleccionado ya se encuentran registradas para este estudiante.");
        }

        // Cantidad real de cuotas que se van a insertar
        $cantidadNuevasEfectivas = count($fechasNuevasEfectivas);

        // -------------------------------------------------------------
        // 3. Ejecutar actualización y creación de cuotas transaccionalmente
        // -------------------------------------------------------------
        DB::transaction(function () use ($cronograma, $especialidad, $fechasNuevasEfectivas, $cantidadNuevasEfectivas) {

            // Calcular los nuevos totales basados únicamente en las cuotas nuevas reales
            $cuotasAnteriores = (int) $cronograma->num_cuotas;
            $nuevasCuotasTotales = $cuotasAnteriores + $cantidadNuevasEfectivas;
            $montoAdicional = $cantidadNuevasEfectivas * $especialidad->costo_mensual;
            $nuevoMontoTotal = $cronograma->monto_total + $montoAdicional;

            $cronograma->update([
                'num_cuotas'  => $nuevasCuotasTotales,
                'monto_total' => $nuevoMontoTotal,
            ]);

            // Obtener códigos de Oracle
            $codigoContribuyente = null;
            $codigoEspecialidad = null;
            $oracleService = null;

            try {
                $oracleService = app(\App\Services\OracleTusneService::class);
                if ($this->estudiante && $this->estudiante->nro_documento) {
                    $codigoContribuyente = $oracleService->verificarContribuyenteExistente($this->estudiante->nro_documento);
                }
                if ($especialidad->nombre_especialidad) {
                    $codigoEspecialidad = $this->obtenerCodigoEspecialidad($especialidad->nombre_especialidad);
                }
            } catch (\Exception $e) {
                \Log::warning('Error de conexión a Oracle durante la extensión: ' . $e->getMessage());
            }

            // Crear los nuevos pagos omitiendo los meses duplicados
            foreach ($fechasNuevasEfectivas as $index => $fechaVence) {
                $nroCuotaFisica = $cuotasAnteriores + ($index + 1);
                $numLiquidacion = null;
                $fechaLiquidacion = null;

                if ($codigoContribuyente && $codigoEspecialidad && $oracleService) {
                    try {
                        $numLiquidacion = $oracleService->generarCodigoLiquidacion($codigoEspecialidad, $codigoContribuyente);
                        if ($numLiquidacion) {
                            $fechaLiquidacion = now();
                            $oracleService->actualizarFechaVencimiento($fechaVence, $numLiquidacion);
                        }
                    } catch (\Exception $e) {
                        \Log::error("Error en Oracle generando cuota {$nroCuotaFisica}: " . $e->getMessage());
                    }
                }

                $estadoOracle = 'Pendiente';
                if ($numLiquidacion && $oracleService) {
                    try {
                        $estadoOracle = $oracleService->obtenerEstadoLiquidacion($numLiquidacion) ?? 'Pendiente';
                    } catch (\Exception $e) {
                        \Log::warning("No se pudo obtener estado de Oracle para la cuota {$nroCuotaFisica}");
                    }
                }

                $cronograma->pagos()->create([
                    'nro_cuota'         => $nroCuotaFisica,
                    'monto'             => $especialidad->costo_mensual,
                    'estado'            => $estadoOracle,
                    'fecha_vencimiento' => $fechaVence,
                    'num_liquidacion'   => $numLiquidacion,
                    'fecha_liquidacion' => $fechaLiquidacion,
                ]);
            }

            // 5. Reordenar todas las cuotas cronológicamente (evita colisiones con la restricción UNIQUE)
            $this->reordenarCuotasCronologicamente();
        });

        return $cronograma;
    }

    public function cambiarHorario(int $horario_id) {
        $this->update([
            'horario_id' => $horario_id
        ]);
    }
    /**
     * Ordena cronológicamente todos los pagos de la matrícula 
     * y renumera las cuotas (1, 2, 3...) según su fecha de vencimiento.
     * Evita colisiones con el índice UNIQUE compuesto [cronograma_id, nro_cuota].
     */
    public function reordenarCuotasCronologicamente(): void
    {
        $cronograma = $this->cronograma;

        if (!$cronograma) {
            return;
        }

        // Usamos una transacción de base de datos para garantizar que el proceso sea atómico.
        // Si algo falla a mitad de camino, todo vuelve a su estado original.
        DB::transaction(function () use ($cronograma) {

            // 1. Obtener los pagos actuales de este cronograma (orden inverso para evitar colisiones en el desplazamiento)
            $pagosParaDesplazar = $cronograma->pagos()
                ->orderBy('nro_cuota', 'desc')
                ->get();

            // 2. Sumamos un offset (+1000) a todas las cuotas actuales para liberar los casilleros del 1 al N.
            foreach ($pagosParaDesplazar as $pago) {
                $pago->updateQuietly([
                    'nro_cuota' => $pago->nro_cuota + 1000
                ]);
            }

            // 3. Volvemos a consultar los pagos, pero ahora ordenados estrictamente por fecha de vencimiento
            $pagosOrdenados = $cronograma->pagos()
                ->orderBy('fecha_vencimiento', 'asc')
                ->get();

            // 4. Asignamos los números correlativos finales (1, 2, 3...) que ahora están completamente libres
            foreach ($pagosOrdenados as $index => $pago) {
                $pago->updateQuietly([
                    'nro_cuota' => $index + 1
                ]);
            }
        });
    }

    /**
     * Obtiene el rango de fechas en el que el estudiante estará académicamente activo
     * basándose estrictamente en la fecha de vencimiento de su primer y último pago.
     * 
     * @return array{inicio: Carbon|null, fin: Carbon|null}
     */
    public function obtenerRangoEstudios(): array
    {
        $cronograma = $this->cronograma;

        if (!$cronograma) {
            return ['inicio' => null, 'fin' => null];
        }

        // Obtener la fecha de vencimiento del pago más antiguo y del más reciente
        $minPago = $cronograma->pagos()->min('fecha_vencimiento');
        $maxPago = $cronograma->pagos()->max('fecha_vencimiento');

        if (!$minPago || !$maxPago) {
            return ['inicio' => null, 'fin' => null];
        }

        // Si el pago vence el 31/03, asumimos que estudió todo Marzo (inicio: 01/03)
        $rangoInicio = Carbon::parse($minPago)->startOfMonth();
        $rangoFin    = Carbon::parse($maxPago)->endOfMonth();

        return [
            'inicio' => $rangoInicio,
            'fin'    => $rangoFin
        ];
    }

    /**
     * Devuelve la colección de Cursos (o Módulos) en los que el alumno
     * está académicamente apto según las fechas de sus pagos.
     */
    /**
     * Devuelve la colección de Cursos (o Módulos) en los que el alumno
     * está académicamente apto, respetando si es matrícula individual o completa.
     */
    public function obtenerCursosActivos(): \Illuminate\Support\Collection
    {
        // -----------------------------------------------------------------
        // CASO 1: Matrículas Individuales (CURSO, MODULO, UNIDAD)
        // Retornar ÚNICAMENTE el curso que fue seleccionado explícitamente
        // -----------------------------------------------------------------
        if (in_array($this->tipo_matricula, [TipoMatricula::CURSO, TipoMatricula::MODULO, TipoMatricula::UNIDAD], true)) {
            if ($this->id_curso) {
                $cursoEstructural = $this->curso;
                return $cursoEstructural ? collect([$cursoEstructural]) : collect();
            }
            return collect();
        }

        // -----------------------------------------------------------------
        // CASO 2: Matrículas Completas (PROGRAMA, FORMACION_CONTINUA)
        // Calcular dinámicamente por cruce de fechas de vencimiento de pagos
        // -----------------------------------------------------------------
        $rango = $this->obtenerRangoEstudios();

        if (!$rango['inicio'] || !$rango['fin']) {
            return collect();
        }

        $programa = $this->horario?->programa;

        if (!$programa) {
            return collect();
        }

        // Filtrado dinámico por fechas de inicio y fin reales de cada curso
        return $programa->cursos()
            ->where(function ($query) use ($rango) {
                $query->where('fecha_inicio', '<=', $rango['fin'])
                    ->where('fecha_termino', '>=', $rango['inicio']);
            })
            ->orderBy('fecha_inicio', 'asc')
            ->get();
    }

    /**
     * Devuelve la colección de Unidades didácticas activas para el alumno,
     * mapeadas a partir de los cursos/módulos que le corresponden en su rango.
     */
    public function obtenerUnidadesActivas(): \Illuminate\Support\Collection
    {
        // Obtener los IDs de los cursos/módulos activos para el alumno
        $cursosActivosIds = $this->obtenerCursosActivos()->pluck('id_curso');

        if ($cursosActivosIds->isEmpty()) {
            return collect();
        }

        // Retorna las unidades que pertenecen a esos cursos activos
        return \App\Models\Unidad::whereIn('id_curso', $cursosActivosIds)
            ->ordenado() // Scope de ordenamiento que tienes en tu modelo Unidad
            ->get();
    }
}
