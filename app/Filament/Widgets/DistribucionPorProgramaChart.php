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
        
        return $dashboardService->getDistribucionPorPrograma();
    }

    protected function getType(): string
    {
        return 'pie';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
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
