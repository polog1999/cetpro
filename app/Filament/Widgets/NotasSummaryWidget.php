<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Nota;
use App\Models\Matricula;
use App\Models\Horario;
use Illuminate\Support\Facades\Auth;

class NotasSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user?->esProfesor() ?? false;
    }
    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->esProfesor() || !$user->docente_id) {
            return [];
        }
        $totalEstudiantes = Matricula::whereHas('horario', function ($query) use ($user) {
            $query->where('id_docente', $user->docente_id);
        })->count();
        $totalNotas = Nota::where('docente_id', $user->docente_id)->count();
        $horariosAsignados = Horario::where('id_docente', $user->docente_id)->count();
        return [
            Stat::make('Total de Estudiantes', $totalEstudiantes)
                ->description('En mis horarios')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Horarios Asignados', $horariosAsignados)
                ->description('Activos')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
            Stat::make('Notas Registradas', $totalNotas)
                ->description('Total de evaluaciones')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];
    }
}
