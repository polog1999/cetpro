<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Services\DashboardService;

class AccionesDelDia extends Widget
{
    protected string $view = 'filament.widgets.acciones-del-dia';
    protected static ?int $sort = 5;
    
    public array $acciones = [];
    
    public function mount(): void
    {
        $dashboardService = app(DashboardService::class);
        $data = $dashboardService->getAccionesDelDia();
        
        $this->acciones = [
            [
                'titulo' => 'Cuotas Vencidas',
                'contador' => $data['cuotas_vencidas'],
                'icono' => 'heroicon-o-exclamation-circle',
                'color' => 'danger',
                'url' => '/admin/pagos?tableFilters[estado][value]=vencido',
            ],
            [
                'titulo' => 'Por Vencer (7 días)',
                'contador' => $data['por_vencer'],
                'icono' => 'heroicon-o-clock',
                'color' => 'warning',
                'url' => '/admin/pagos?por_vencer=7',
            ],
            [
                'titulo' => 'Sin Cronograma',
                'contador' => $data['sin_cronograma'],
                'icono' => 'heroicon-o-document-minus',
                'color' => 'warning',
                'url' => '/admin/matriculas?sin_cronograma=true',
            ],
            [
                'titulo' => 'Sin Horario Asignado',
                'contador' => $data['sin_horario'],
                'icono' => 'heroicon-o-calendar',
                'color' => 'info',
                'url' => '/admin/matriculas?sin_horario=true',
            ],
            [
                'titulo' => 'Pagos sin Evidencia',
                'contador' => $data['sin_evidencia'],
                'icono' => 'heroicon-o-photo',
                'color' => 'gray',
                'url' => '/admin/pagos?sin_evidencia=true',
            ],
        ];
    }
}
