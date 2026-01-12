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
        
        $data = $dashboardService->getMatriculasPorMes($filters);
        
        return [
            'labels' => $data['labels'],
            'datasets' => [
                [
                    'label' => 'Matrículas',
                    'data' => $data['datasets'][0]['data'],
                    'fill' => true,
                    'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                    'borderColor' => 'rgb(79, 70, 229)',
                    'borderWidth' => 3,
                    'tension' => 0.4,
                    'pointBackgroundColor' => 'rgb(79, 70, 229)',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 7,
                ]
            ]
        ];
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
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
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
