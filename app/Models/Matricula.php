<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

use App\Models\Estudiante;
use App\Models\Seccion;
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
        'seccion_id',
        'estado',
        'tipo_matricula',
        'id_curso',
    ];

    protected $casts = [
        'estado'         => EstadoMatricula::class,
        'tipo_matricula' => TipoMatricula::class,
    ];

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class, 'seccion_id', 'id_seccion');
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

        // 1) CURSO LIBRE -> duración del curso
        if ($this->tipo_matricula === TipoMatricula::CURSO_LIBRE) {
            $curso = $this->curso;

            if (! $curso) {
                return null; // no hay curso asociado
            }

            $duracion     = $curso->duracion;                // p.ej. en meses o nº de cuotas
            $especialidad = $curso->programa?->especialidad; // programa del curso -> especialidad
        }

        // 2) PROG_ESTUDIO o FORM_CONTINUA -> duración del programa
        if (in_array($this->tipo_matricula, [TipoMatricula::PROG_ESTUDIO, TipoMatricula::FORM_CONTINUA], true)) {
            $programa = $this->seccion?->programa;

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

        // Caso CURSO LIBRE: una cuota con el fin de mes de fecha_inicio del curso
        if ($this->tipo_matricula === TipoMatricula::CURSO_LIBRE) {
            $curso = $this->curso;

            if ($curso && $curso->fecha_inicio) {
                $inicio   = Carbon::parse($curso->fecha_inicio);
                $fechas[] = $inicio->copy()->endOfMonth();
            }
        } else {
            // Programa de estudio / formación continua
            $programa = $this->seccion?->programa;

            if ($programa) {
                $cursos = $programa->cursos()
                    ->orderBy('fecha_inicio')
                    ->get();

                // Para cada curso, tomamos el fin de mes de su fecha_inicio
                foreach ($cursos as $curso) {
                    if ($curso->fecha_inicio) {
                        $fechas[] = Carbon::parse($curso->fecha_inicio)->endOfMonth();
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
     * Cuando se crea la matrícula, se genera automáticamente el cronograma
     * (y dentro de él, sus pagos).
     */
    protected static function booted()
    {
        static::created(function (Matricula $matricula) {
            $matricula->generarCronograma();
        });
    }
}
