<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Services\DashboardService;

class ActividadRecienteTable extends Widget
{
    protected static ?int $sort = 5;
    protected string $view = 'filament.widgets.actividad-reciente';
    
    public $actividades;
    
    public function mount(): void
    {
        $dashboardService = app(DashboardService::class);
        $this->actividades = $dashboardService->getActividadReciente([], 20);
    }
}
