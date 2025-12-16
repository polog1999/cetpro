<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Horario;
use App\Models\Programa;
use App\Enums\EstadoPago;
use App\Enums\EstadoMatricula;
use App\Enums\TipoMatricula;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Servicio para gestionar datos del Dashboard
 * Centraliza toda la lógica de negocio para KPIs, gráficos y tablas
 */
class DashboardService
{
    /**
     * Obtiene todos los KPIs filtrados
     * 
     * @param array $filters ['desde' => Carbon, 'hasta' => Carbon, 'programa_id' => int]
     * @return array
     */
    public function getKPIs(array $filters = []): array
    {
        $cacheKey = 'dashboard.kpis.' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 60, function () use ($filters) {
            return [
                'estudiantes_activos' => $this->getEstudiantesActivos(),
                'matriculas_mes' => $this->getMatriculasDelMes($filters),
                'ingresos_mes' => $this->getIngresosDelMes($filters),
                'pendiente_cobrar' => $this->getPendientePorCobrar($filters),
                'morosos' => $this->getMorosos($filters),
                'cupos_disponibles' => $this->getCuposDisponibles($filters),
                'horarios_activos' => $this->getHorariosActivos($filters),
                'matriculas_incompletas' => $this->getMatriculasIncompletas(),
            ];
        });
    }

    /**
     * KPI 1: Total de estudiantes activos
     * Un estudiante es considerado activo si tiene al menos una matrícula no anulada
     */
    protected function getEstudiantesActivos(): int
    {
        return Estudiante::whereHas('matriculas', function ($query) {
            $query->where('estado', '!=', EstadoMatricula::ANULADO->value);
        })->distinct()->count('id');
    }

    /**
     * KPI 2: Matrículas creadas en el período
     */
    protected function getMatriculasDelMes(array $filters): int
    {
        $query = Matricula::query();
        
        if (isset($filters['desde']) && isset($filters['hasta'])) {
            $query->whereBetween('created_at', [
                $filters['desde'],
                $filters['hasta']
            ]);
        }
        
        if (isset($filters['programa_id'])) {
            $query->whereHas('horario', function ($q) use ($filters) {
                $q->where('id_programa', $filters['programa_id']);
            });
        }
        
        return $query->count();
    }

    /**
     * KPI 3: Total pagado en el período
     */
    protected function getIngresosDelMes(array $filters): float
    {
        $query = Pago::where('estado', EstadoPago::PAGADO);
        
        if (isset($filters['desde']) && isset($filters['hasta'])) {
            $query->whereBetween('fecha_pago', [
                $filters['desde'],
                $filters['hasta']
            ]);
        }
        
        if (isset($filters['programa_id'])) {
            $query->whereHas('cronograma.matricula.horario', function ($q) use ($filters) {
                $q->where('id_programa', $filters['programa_id']);
            });
        }
        
        return (float) $query->sum('monto');
    }

    /**
     * KPI 4: Total pendiente de cobrar
     */
    protected function getPendientePorCobrar(array $filters): float
    {
        $query = Pago::whereIn('estado', [EstadoPago::PENDIENTE, EstadoPago::VENCIDO]);
        
        if (isset($filters['programa_id'])) {
            $query->whereHas('cronograma.matricula.horario', function ($q) use ($filters) {
                $q->where('id_programa', $filters['programa_id']);
            });
        }
        
        return (float) $query->sum('monto');
    }

    /**
     * KPI 5: Cantidad de estudiantes morosos (con al menos 1 pago vencido)
     */
    protected function getMorosos(array $filters): int
    {
        $query = Estudiante::whereHas('matriculas.cronograma.pagos', function ($q) {
            $q->where('estado', EstadoPago::VENCIDO);
        });
        
        if (isset($filters['programa_id'])) {
            $query->whereHas('matriculas.horario', function ($q) use ($filters) {
                $q->where('id_programa', $filters['programa_id']);
            });
        }
        
        return $query->distinct()->count('id');
    }

    /**
     * KPI 6: Cupos disponibles en horarios activos
     */
    protected function getCuposDisponibles(array $filters): int
    {
        $query = Horario::where('activo', true);
        
        if (isset($filters['programa_id'])) {
            $query->where('id_programa', $filters['programa_id']);
        }
        
        $horarios = $query->get();
        $cuposDisponibles = 0;
        
        foreach ($horarios as $horario) {
            $matriculados = Matricula::where('horario_id', $horario->id_horario)
                ->where('estado', '!=', EstadoMatricula::ANULADO->value)
                ->count();
            
            $cuposDisponibles += max(0, $horario->vacantes - $matriculados);
        }
        
        return $cuposDisponibles;
    }

    /**
     * KPI 7: Horarios activos
     */
    protected function getHorariosActivos(array $filters): int
    {
        $query = Horario::where('activo', true);
        
        if (isset($filters['programa_id'])) {
            $query->where('id_programa', $filters['programa_id']);
        }
        
        return $query->count();
    }

    /**
     * KPI 8: Matrículas sin cronograma generado
     */
    protected function getMatriculasIncompletas(): int
    {
        return Matricula::doesntHave('cronograma')
            ->where('estado', '!=', EstadoMatricula::ANULADO->value)
            ->count();
    }

    /**
     * Obtiene datos para gráfico de matrículas por mes (últimos 12 meses)
     */
    public function getMatriculasPorMes(array $filters = []): array
    {
        $meses = [];
        $datos = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $meses[] = $fecha->format('M Y');
            
            $count = Matricula::whereYear('created_at', $fecha->year)
                ->whereMonth('created_at', $fecha->month)
                ->when(isset($filters['programa_id']), function ($q) use ($filters) {
                    $q->whereHas('horario', fn($query) => 
                        $query->where('id_programa', $filters['programa_id'])
                    );
                })
                ->count();
            
            $datos[] = $count;
        }
        
        return [
            'labels' => $meses,
            'datasets' => [
                [
                    'label' => 'Matrículas',
                    'data' => $datos,
                ]
            ]
        ];
    }

    /**
     * Obtiene datos para gráfico Pagado vs Pendiente (últimos 6 meses)
     */
    public function getPagadoVsPendiente(array $filters = []): array
    {
        $meses = [];
        $pagado = [];
        $pendiente = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $fecha = now()->subMonths($i);
            $meses[] = $fecha->format('M Y');
            
            $iniciomes = $fecha->copy()->startOfMonth();
            $finMes = $fecha->copy()->endOfMonth();
            
            $pagadoMes = Pago::where('estado', EstadoPago::PAGADO)
                ->whereBetween('fecha_pago', [$iniciomes, $finMes])
                ->when(isset($filters['programa_id']), function ($q) use ($filters) {
                    $q->whereHas('cronograma.matricula.horario', fn($query) => 
                        $query->where('id_programa', $filters['programa_id'])
                    );
                })
                ->sum('monto');
            
            $pendienteMes = Pago::whereIn('estado', [EstadoPago::PENDIENTE, EstadoPago::VENCIDO])
                ->whereBetween('fecha_vencimiento', [$iniciomes, $finMes])
                ->when(isset($filters['programa_id']), function ($q) use ($filters) {
                    $q->whereHas('cronograma.matricula.horario', fn($query) => 
                        $query->where('id_programa', $filters['programa_id'])
                    );
                })
                ->sum('monto');
            
            $pagado[] = $pagadoMes;
            $pendiente[] = $pendienteMes;
        }
        
        return [
            'labels' => $meses,
            'datasets' => [
                [
                    'label' => 'Pagado',
                    'data' => $pagado,
                    'backgroundColor' => '#10b981',
                ],
                [
                    'label' => 'Pendiente',
                    'data' => $pendiente,
                    'backgroundColor' => '#f59e0b',
                ]
            ]
        ];
    }

    /**
     * Obtiene datos para gráfico de distribución por programa
     */
    public function getDistribucionPorPrograma(): array
    {
        $datos = Programa::select('programas.*')
            ->selectRaw('(
                SELECT COUNT(DISTINCT matriculas.id)
                FROM horarios
                INNER JOIN matriculas ON matriculas.horario_id = horarios.id_horario
                WHERE horarios.id_programa = programas.id_programa
                AND matriculas.estado != ?
            ) as matriculados', [EstadoMatricula::ANULADO->value])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('horarios')
                    ->join('matriculas', 'matriculas.horario_id', '=', 'horarios.id_horario')
                    ->whereColumn('horarios.id_programa', 'programas.id_programa')
                    ->where('matriculas.estado', '!=', EstadoMatricula::ANULADO->value);
            })
            ->orderByDesc('matriculados')
            ->limit(10)
            ->get();
        
        return [
            'labels' => $datos->pluck('nombre_programa')->toArray(),
            'datasets' => [
                [
                    'label' => 'Matriculados',
                    'data' => $datos->pluck('matriculados')->toArray(),
                ]
            ]
        ];
    }

    /**
     * Obtiene top 10 estudiantes morosos
     */
    public function getTopMorosidad(array $filters = [], int $limit = 10)
    {
        return Estudiante::select('estudiantes.*')
            ->join('matriculas', 'matriculas.estudiante_id', '=', 'estudiantes.id')
            ->join('cronogramas', 'cronogramas.matricula_id', '=', 'matriculas.id')
            ->join('pagos', 'pagos.cronograma_id', '=', 'cronogramas.id')
            ->where('pagos.estado', EstadoPago::VENCIDO)
            ->when(isset($filters['programa_id']), function ($q) use ($filters) {
                $q->join('horarios', 'horarios.id_horario', '=', 'matriculas.horario_id')
                    ->where('horarios.id_programa', $filters['programa_id']);
            })
            ->groupBy('estudiantes.id')
            ->selectRaw('estudiantes.*, SUM(pagos.monto) as deuda_total, MAX(pagos.fecha_vencimiento) as ultima_vencida')
            ->orderByDesc('deuda_total')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtiene actividad reciente (pagos + matrículas)
     */
    public function getActividadReciente(array $filters = [], int $limit = 20)
    {
        $pagos = Pago::select(
                'pagos.id',
                'pagos.created_at as fecha',
                DB::raw("'Pago registrado' as tipo"),
                DB::raw("CONCAT('Pago ', pagos.codigo, ' - ', pagos.monto, ' Soles.') as descripcion"),
                'usuarios.usuario as usuario'
            )
            ->join('usuarios', 'usuarios.id', '=', 'pagos.usuario_id')
            ->where('pagos.estado', EstadoPago::PAGADO)
            ->limit($limit / 2);
        
        $matriculas = Matricula::select(
                'matriculas.id',
                'matriculas.created_at as fecha',
                DB::raw("'Matrícula creada' as tipo"),
                DB::raw("CONCAT('Matrícula ', matriculas.codigo_inscripcion) as descripcion"),
                DB::raw("'Sistema' as usuario")
            )
            ->limit($limit / 2);
        
        return $pagos->union($matriculas)
            ->orderByDesc('fecha')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtiene contadores para "Acciones del Día"
     */
    public function getAccionesDelDia(array $filters = []): array
    {
        return [
            'cuotas_vencidas' => Pago::where('estado', EstadoPago::VENCIDO)->count(),
            
            'por_vencer' => Pago::where('estado', EstadoPago::PENDIENTE)
                ->whereBetween('fecha_vencimiento', [now(), now()->addDays(7)])
                ->count(),
            
            'sin_cronograma' => Matricula::doesntHave('cronograma')
                ->where('estado', '!=', EstadoMatricula::ANULADO->value)
                ->count(),
            
            'sin_horario' => Matricula::whereNull('horario_id')
                ->where('tipo_matricula', TipoMatricula::PROGRAMA->value)
                ->where('estado', '!=', EstadoMatricula::ANULADO->value)
                ->count(),
            
            'sin_evidencia' => Pago::where('estado', EstadoPago::PAGADO)
                ->whereNull('evidencia_path')
                ->count(),
        ];
    }
}
