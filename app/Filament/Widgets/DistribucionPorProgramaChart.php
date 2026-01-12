<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\DashboardService;

class DistribucionPorProgramaChart extends ChartWidget
{
    protected ?string $heading = 'Programas por Matrícula';
    protected static ?int $sort = 4;
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $dashboardService = app(DashboardService::class);
        
        $data = $dashboardService->getDistribucionPorPrograma();
        
        // Paleta de colores profesionales
        $colors = [
            'rgba(79, 70, 229, 0.8)',   // Índigo
            'rgba(16, 185, 129, 0.8)',  // Esmeralda
            'rgba(245, 158, 11, 0.8)',  // Ámbar
            'rgba(239, 68, 68, 0.8)',   // Rojo
            'rgba(59, 130, 246, 0.8)',  // Azul
            'rgba(168, 85, 247, 0.8)',  // Púrpura
            'rgba(20, 184, 166, 0.8)',  // Teal
            'rgba(251, 146, 60, 0.8)',  // Naranja
            'rgba(236, 72, 153, 0.8)',  // Rosa
            'rgba(34, 197, 94, 0.8)',   // Verde
        ];
        
        $borderColors = array_map(function($color) {
            return str_replace('0.8)', '1)', $color);
        }, $colors);
        
        return [
            'labels' => $data['labels'],
            'datasets' => [
                [
                    'label' => 'Matriculados',
                    'data' => $data['datasets'][0]['data'],
                    'backgroundColor' => array_slice($colors, 0, count($data['labels'])),
                    'borderColor' => array_slice($borderColors, 0, count($data['labels'])),
                    'borderWidth' => 2,
                    'hoverOffset' => 10,
                ]
            ]
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
            ],
            'cutout' => '60%',
            'responsive' => true,
            'maintainAspectRatio' => true,
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
