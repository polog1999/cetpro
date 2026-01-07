<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Services\DashboardService;
use App\Models\Estudiante;
use Filament\Actions\Action;

class TopMorosidadTable extends BaseWidget
{
    use InteractsWithPageFilters;
    
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        $dashboardService = app(DashboardService::class);
        
        $filters = [];
        if ($this->filters['programa_id'] ?? null) {
            $filters['programa_id'] = $this->filters['programa_id'];
        }
        
        return $table
            ->heading('Morosidad')
            ->description('Estudiantes con mayor deuda vencida')
            ->query(
                fn () => Estudiante::query()
                    ->whereIn('id', $dashboardService->getTopMorosidad($filters)->pluck('id'))
            )
            ->columns([
                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Estudiante')
                    ->searchable(['nombres', 'apellido_paterno', 'apellido_materno'])
                    ->sortable(false),
                
                Tables\Columns\TextColumn::make('nro_documento')
                    ->label('DNI')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('deuda_total')
                    ->label('Deuda Total')
                    ->money('PEN')
                    ->sortable()
                    ->getStateUsing(function ($record) use ($dashboardService, $filters) {
                        $morosos = $dashboardService->getTopMorosidad($filters);
                        $moroso = $morosos->firstWhere('id', $record->id);
                        return $moroso->deuda_total ?? 0;
                    }),
                
                Tables\Columns\TextColumn::make('dias_atraso')
                    ->label('Días de Atraso')
                    ->badge()
                    ->color('danger')
                    ->getStateUsing(function ($record) {
                        // Calcular días desde la última cuota vencida
                        $ultimaVencida = $record->matriculas()
                            ->join('cronogramas', 'cronogramas.matricula_id', '=', 'matriculas.id')
                            ->join('pagos', 'pagos.cronograma_id', '=', 'cronogramas.id')
                            ->whereRaw("LOWER(pagos.estado) LIKE '%vencido%'")
                            ->max('pagos.fecha_vencimiento');
                        
                        if (!$ultimaVencida) {
                            return 0;
                        }
                        
                        return now()->diffInDays(\Carbon\Carbon::parse($ultimaVencida));
                    }),
            ])
            ->actions([
                Action::make('ver_pagos')
                    ->label('Ver Pagos')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('info')
                    ->modalHeading(fn($record) => "Pagos de {$record->nombre_completo}")
                    ->modalWidth('5xl')
                    ->modalContent(fn($record) => view('filament.estudiantes.ver-pagos-modal', [
                        'estudiante' => $record->load([
                            'matriculas.horario.programa',
                            'matriculas.curso',
                            'matriculas.cronograma.pagos' => function($query) {
                                $query->orderBy('nro_cuota');
                            }
                        ])
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->paginated(false);
    }
    
    /**
     * No visible para profesores
     */
    public static function canView(): bool
    {
        return !auth()->user()?->esProfesor();
    }
}
