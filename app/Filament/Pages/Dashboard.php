<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Components;
use Filament\Schemas\Schema;
use App\Models\Programa;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;
    
    /**
     * Define los filtros globales del dashboard
     */
    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\DatePicker::make('desde')
                    ->label('Desde')
                    ->default(now()->startOfMonth())
                    ->native(false)
                    ->columnSpan(1),
                
                Components\DatePicker::make('hasta')
                    ->label('Hasta')
                    ->default(now()->endOfMonth())
                    ->native(false)
                    ->columnSpan(1),
                
                Components\Select::make('programa_id')
                    ->label('Programa')
                    ->options(Programa::pluck('nombre_programa', 'id_programa'))
                    ->searchable()
                    ->placeholder('Todos los programas')
                    ->columnSpan(1),
            ])
            ->columns(3);
    }
    
    /**
     * Widgets del header (aparecen arriba de los filtros)
     */
    public function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\WelcomeWidget::class,
        ];
    }
    
    /**
     * Obtiene los widgets del dashboard en orden específico
     */
    public function getWidgets(): array
    {
        return [
            // 1. KPIs principales (5 tarjetas destacadas)
            \App\Filament\Widgets\KPIsGenerales::class,
            
            // 2. KPIs secundarios (3 tarjetas compactas)
            \App\Filament\Widgets\SecondaryKPIsWidget::class,
            
            // 3. Gráficos (2 columnas)
            \App\Filament\Widgets\MatriculasPorMesChart::class,
            \App\Filament\Widgets\PagadoVsPendienteChart::class,
            \App\Filament\Widgets\DistribucionPorProgramaChart::class,
            
            // 4. Tablas (full width)
            \App\Filament\Widgets\ActividadRecienteTable::class,
            
            // 5. Widget para profesores
            \App\Filament\Widgets\NotasSummaryWidget::class,
        ];
    }
    
    /**
     * Configuración de columnas para widgets
     */
    public function getColumns(): int | array
    {
        return 2; // Dashboard de 2 columnas por defecto
    }
}
