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
                ->description('Matriculados en el sistema')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->extraAttributes([
                    'class' => 'ring-2 ring-success-500/20 hover:ring-success-500/40 transition-all duration-300',
                ]),

            Stat::make('Matrículas del Mes', number_format($kpis['matriculas_mes']))
                ->description('Nuevas inscripciones')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info')
                ->chart([3, 5, 7, 6, 8, 10, 12])
                ->extraAttributes([
                    'class' => 'ring-2 ring-info-500/20 hover:ring-info-500/40 transition-all duration-300',
                ]),

            Stat::make('Ingresos del Mes', 'S/. ' . number_format($kpis['ingresos_mes'], 2))
                ->description('Total recaudado')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([10, 15, 20, 18, 22, 25, 30])
                ->extraAttributes([
                    'class' => 'ring-2 ring-success-500/20 hover:ring-success-500/40 transition-all duration-300',
                ]),

            Stat::make('Pendiente de Cobro', 'S/. ' . number_format($kpis['pendiente_cobrar'], 2))
                ->description('Por cobrar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([30, 28, 26, 24, 22, 20, 18])
                ->extraAttributes([
                    'class' => 'ring-2 ring-warning-500/20 hover:ring-warning-500/40 transition-all duration-300',
                ]),

            Stat::make('Estudiantes Morosos', number_format($kpis['morosos']))
                ->description('Con cuotas vencidas')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->chart([5, 6, 7, 8, 7, 6, 5])
                ->extraAttributes([
                    'class' => 'ring-2 ring-danger-500/20 hover:ring-danger-500/40 transition-all duration-300',
                ]),
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
     * Control de visibilidad por rol - No visible para profesores
     */
    public static function canView(): bool
    {
        $user = auth()->user();
        
        // Profesores no ven widgets administrativos
        if ($user?->esProfesor()) {
            return false;
        }
        
        return true;
    }
}
