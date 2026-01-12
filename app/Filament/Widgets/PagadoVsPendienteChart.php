<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Services\DashboardService;

class PagadoVsPendienteChart extends ChartWidget
{
    use InteractsWithPageFilters;
    
    protected ?string $heading = 'Pagado vs Pendiente';
    protected static ?int $sort = 3;
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $dashboardService = app(DashboardService::class);
        
        $filters = [];
        if ($this->filters['programa_id'] ?? null) {
            $filters['programa_id'] = $this->filters['programa_id'];
        }
        
        $data = $dashboardService->getPagadoVsPendiente($filters);
        
        return [
            'labels' => $data['labels'],
            'datasets' => [
                [
                    'label' => 'Pagado',
                    'data' => $data['datasets'][0]['data'],
                    'backgroundColor' => 'rgba(16, 185, 129, 0.8)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                    'borderSkipped' => false,
                ],
                [
                    'label' => 'Pendiente',
                    'data' => $data['datasets'][1]['data'],
                    'backgroundColor' => 'rgba(245, 158, 11, 0.8)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                    'borderSkipped' => false,
                ]
            ]
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) { return context.dataset.label + ': S/. ' + context.raw.toLocaleString(); }",
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                    'ticks' => [
                        'callback' => "function(value) { return 'S/. ' + value.toLocaleString(); }",
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
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
