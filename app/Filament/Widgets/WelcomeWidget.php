<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Services\DashboardService;
use Carbon\Carbon;

class WelcomeWidget extends Widget
{
    protected static ?int $sort = 0;
    protected int | string | array $columnSpan = 'full';
    protected string $view = 'filament.widgets.welcome-widget';
    
    public string $greeting;
    public string $userName;
    public string $currentDate;
    public array $alerts;
    
    public function mount(): void
    {
        $user = Auth::user();
        $hour = now()->hour;
        
        // Saludo según hora del día
        if ($hour < 12) {
            $this->greeting = 'Buenos días';
        } elseif ($hour < 18) {
            $this->greeting = 'Buenas tardes';
        } else {
            $this->greeting = 'Buenas noches';
        }
        
        $this->userName = $user?->nombre ?? $user?->usuario ?? 'Administrador';
        $this->currentDate = Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY');
        
        // Obtener alertas del día
        $dashboardService = app(DashboardService::class);
        $acciones = $dashboardService->getAccionesDelDia();
        
        $this->alerts = [];
        
        if ($acciones['cuotas_vencidas'] > 0) {
            $this->alerts[] = [
                'icon' => 'heroicon-o-exclamation-circle',
                'color' => 'danger',
                'message' => $acciones['cuotas_vencidas'] . ' cuotas vencidas requieren atención',
            ];
        }
        
        if ($acciones['por_vencer'] > 0) {
            $this->alerts[] = [
                'icon' => 'heroicon-o-clock',
                'color' => 'warning',
                'message' => $acciones['por_vencer'] . ' cuotas por vencer esta semana',
            ];
        }
        
        if ($acciones['sin_cronograma'] > 0) {
            $this->alerts[] = [
                'icon' => 'heroicon-o-document-minus',
                'color' => 'info',
                'message' => $acciones['sin_cronograma'] . ' matrículas sin cronograma de pagos',
            ];
        }
    }
    
    /**
     * No visible para profesores
     */
    public static function canView(): bool
    {
        return !auth()->user()?->esProfesor();
    }
}
