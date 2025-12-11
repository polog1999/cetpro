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
use App\Enums\EstadoPago;

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
    ];

    protected $casts = [
        'estado'         => EstadoMatricula::class,
        'tipo_matricula' => TipoMatricula::class,
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
     * Genera y guarda el cronograma de pagos de esta matrícula
     * y sus cuotas (pagos) correspondientes.
     */
    public function generarCronograma(): ?Cronograma
    {
        $duracion     = null;
        $especialidad = null;

        // 1) CURSO -> duración del curso
        if ($this->tipo_matricula === TipoMatricula::CURSO) {
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

        // Fechas de vencimiento para cada cuota
        $fechasVencimiento = $this->calcularFechasVencimientoCuotas($numCuotas);

        for ($i = 1; $i <= $numCuotas; $i++) {
            $cronograma->pagos()->create([
                'nro_cuota'         => $i,
                // 'codigo' se genera en el modelo Pago::creating si se deja null
                'monto'             => $montos[$i - 1],
                'estado'            => EstadoPago::PENDIENTE,
                'fecha_vencimiento' => $fechasVencimiento[$i - 1] ?? null,
                'metodo_pago'       => null,
                'fecha_pago'        => null,
                'evidencia_path'    => null,
                'num_liquidacion'   => null,
                'fecha_liquidacion' => null,
            ]);
        }
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
     * Generación automática del código de inscripción y cronograma.
     */
    protected static function booted()
    {
        // ANTES de guardar: generar código de inscripción y validar
        static::creating(function (Matricula $matricula) {
            // 1. Validar vacantes
            $horario = Horario::find($matricula->horario_id);
            if ($horario) {
                $matriculados = Matricula::where('horario_id', $matricula->horario_id)
                    ->where('estado', '!=', EstadoMatricula::ANULADO)
                    ->count();
                
                if ($matriculados >= $horario->vacantes) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'horario_id' => 'No hay vacantes disponibles en este horario.',
                    ]);
                }
            }

            // 2. Validar duplicado
            if ($matricula->estudiante_id && $matricula->horario_id) {
                $exists = Matricula::where('estudiante_id', $matricula->estudiante_id)
                    ->where('horario_id', $matricula->horario_id)
                    ->where('estado', '!=', EstadoMatricula::ANULADO)
                    ->exists();
                
                if ($exists) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'horario_id' => 'El estudiante ya está matriculado en este horario.',
                    ]);
                }
            }

            if (empty($matricula->codigo_inscripcion)) {
                // Obtener el horario y su programa asociado
                // $horario ya obtenido arriba
                
                if ($horario && $horario->id_programa) {
                    // Obtener el año actual
                    $year = now()->format('Y');
                    
                    // Formatear el ID del programa a 3 dígitos
                    $programaId = str_pad($horario->id_programa, 3, '0', STR_PAD_LEFT);
                    
                    // Contar cuántas matrículas ya existen para este programa en este año
                    $prefijo = "{$year}-{$programaId}";
                    $count = static::where('codigo_inscripcion', 'like', "{$prefijo}-%")
                        ->count();
                    
                    // Número secuencial (siguiente después del último)
                    $secuencial = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
                    
                    // Generar código: YYYY-XXX-NNN
                    $matricula->codigo_inscripcion = "{$year}-{$programaId}-{$secuencial}";
                } else {
                    // Fallback si no hay horario o programa
                    $prefijo = now()->format('Y') . '-000';
                    $count = static::where('codigo_inscripcion', 'like', "{$prefijo}-%")->count();
                    $secuencial = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
                    $matricula->codigo_inscripcion = "{$prefijo}-{$secuencial}";
                }
            }
        });
        
        // DESPUÉS de guardar: generar cronograma
        static::created(function (Matricula $matricula) {
            $matricula->generarCronograma();
        });
    }
}
