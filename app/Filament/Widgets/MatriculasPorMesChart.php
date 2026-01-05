<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Services\DashboardService;

class MatriculasPorMesChart extends ChartWidget
{
    use InteractsWithPageFilters;
    
    protected ?string $heading = 'Matrículas por Mes';
    protected static ?int $sort = 2;
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $dashboardService = app(DashboardService::class);
        
        $filters = [];
        if ($this->filters['programa_id'] ?? null) {
            $filters['programa_id'] = $this->filters['programa_id'];
        }
        
        return $dashboardService->getMatriculasPorMes($filters);
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
    
    /**
     * No visible para profesores
     */
    public static function canView(): bool
    {
        return !auth()->user()?->esProfesor();
    }
}
