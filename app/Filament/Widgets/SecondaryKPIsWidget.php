<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\DashboardService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class SecondaryKPIsWidget extends BaseWidget
{
    use InteractsWithPageFilters;
    
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        $dashboardService = app(DashboardService::class);
        $filters = $this->getFiltersArray();
        $kpis = $dashboardService->getKPIs($filters);
        
        return [
            Stat::make('Cupos Disponibles', number_format($kpis['cupos_disponibles']))
                ->description('Vacantes en horarios')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'border-l-4 border-l-primary-500',
                ]),

            Stat::make('Horarios Activos', number_format($kpis['horarios_activos']))
                ->description('Clases en curso')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'border-l-4 border-l-info-500',
                ]),

            Stat::make('Matrículas Incompletas', number_format($kpis['matriculas_incompletas']))
                ->description('Sin cronograma')
                ->descriptionIcon('heroicon-m-document-minus')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'border-l-4 border-l-warning-500',
                ]),
        ];
    }
    
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
     * No visible para profesores
     */
    public static function canView(): bool
    {
        return !auth()->user()?->esProfesor();
    }
}
