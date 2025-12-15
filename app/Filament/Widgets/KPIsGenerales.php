<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\DashboardService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class KPIsGenerales extends BaseWidget
{
    use InteractsWithPageFilters;
    
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        $dashboardService = app(DashboardService::class);
        
        // Obtener filtros globales
        $filters = $this->getFiltersArray();
        
        // Obtener KPIs del servicio
        $kpis = $dashboardService->getKPIs($filters);
        
        return [
            Stat::make('Estudiantes Activos', number_format($kpis['estudiantes_activos']))
                ->description('Total de estudiantes activos en el sistema')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('Matrículas del Mes', number_format($kpis['matriculas_mes']))
                ->description('Nuevas matrículas en el período')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info')
                ->chart([3, 5, 7, 6, 8, 10, 12]),

            Stat::make('Ingresos del Mes', 'S/. ' . number_format($kpis['ingresos_mes'], 2))
                ->description('Total pagado en el período')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([10, 15, 20, 18, 22, 25, 30]),

            Stat::make('Pendiente de Cobrar', 'S/. ' . number_format($kpis['pendiente_cobrar'], 2))
                ->description('Cuotas pendientes y vencidas')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->chart([30, 28, 26, 24, 22, 20, 18]),

            Stat::make('Estudiantes Morosos', number_format($kpis['morosos']))
                ->description('Con al menos 1 cuota vencida')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('danger')
                ->chart([5, 6, 7, 8, 7, 6, 5]),

            Stat::make('Cupos Disponibles', number_format($kpis['cupos_disponibles']))
                ->description('Vacantes en horarios activos')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([50, 48, 45, 43, 40, 38, 35]),

            Stat::make('Horarios Activos', number_format($kpis['horarios_activos']))
                ->description('Clases en curso')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary')
                ->chart([4, 6, 8, 10, 12, 14, 16]),

            Stat::make('Matrículas Incompletas', number_format($kpis['matriculas_incompletas']))
                ->description('Sin cronograma de pagos generado')
                ->descriptionIcon('heroicon-m-document-minus')
                ->color('warning')
                ->chart([10, 9, 8, 7, 6, 5, 4]),
        ];
    }
    
    /**
     * Convierte los filtros de página a formato array
     */
    protected function getFiltersArray(): array
    {
        $filters = [];
        
        if ($this->filters['desde'] ?? null) {
            $filters['desde'] = \Carbon\Carbon::parse($this->filters['desde']);
        }
        
        if ($this->filters['hasta'] ?? null) {
            $filters['hasta'] = \Carbon\Carbon::parse($this->filters['hasta']);
        }
        
        if ($this->filters['programa_id'] ?? null) {
            $filters['programa_id'] = $this->filters['programa_id'];
        }
        
        return $filters;
    }
    
    /**
     * Control de visibilidad por rol
     */
    public static function canView(): bool
    {
        $user = auth()->user();
        
        // Admin ve todo
        if ($user?->role?->es_admin) {
            return true;
        }
        
        // Usuarios con permiso específico (si existe el permiso 'dashboard')
        // Por ahora permitimos a todos los usuarios autenticados
        return true;
    }
}
