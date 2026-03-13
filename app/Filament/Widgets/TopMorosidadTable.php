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
    
    public function rendering(): void
    {
        $this->getTable();
    }

    public function table(Table $table): Table
    {
        $dashboardService = app(DashboardService::class);
        
        $filters = [];
        
        if ($this->filters['desde'] ?? null) {
            $filters['desde'] = \Carbon\Carbon::parse($this->filters['desde']);
        }
        
        if ($this->filters['hasta'] ?? null) {
            $filters['hasta'] = \Carbon\Carbon::parse($this->filters['hasta']);
        }

        if ($this->filters['programa_id'] ?? null) {
            $filters['programa_id'] = $this->filters['programa_id'];
        }
        
        return $table
            ->heading('🚨 Morosidad')
            ->description('Top 10 estudiantes con mayor deuda vencida')
            ->query(
                fn () => Estudiante::query()
                    ->whereIn('id', $dashboardService->getTopMorosidad($filters)->pluck('id'))
            )
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\ImageColumn::make('avatar')
                            ->circular()
                            ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->nombre_completo) . '&background=6366f1&color=fff&size=128')
                            ->size(40)
                            ->grow(false),
                        
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('nombre_completo')
                                ->label('Estudiante')
                                ->weight('bold')
                                ->searchable(['nombres', 'apellido_paterno', 'apellido_materno']),
                            
                            Tables\Columns\TextColumn::make('nro_documento')
                                ->label('DNI')
                                ->size('sm')
                                ->color('gray')
                                ->icon('heroicon-m-identification')
                                ->iconColor('gray'),
                        ]),
                    ]),
                ])->space(2),
                
                Tables\Columns\TextColumn::make('deuda_total')
                    ->label('Deuda Total')
                    ->size('lg')
                    ->weight('bold')
                    ->color('danger')
                    ->alignEnd()
                    ->getStateUsing(function ($record) use ($dashboardService, $filters) {
                        $morosos = $dashboardService->getTopMorosidad($filters);
                        $moroso = $morosos->firstWhere('id', $record->id);
                        return 'S/. ' . number_format($moroso->deuda_total ?? 0, 2);
                    }),
                
                Tables\Columns\TextColumn::make('dias_atraso')
                    ->label('Atraso')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 60 => 'danger',
                        $state >= 30 => 'warning',
                        $state >= 7 => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state . ' días')
                    ->getStateUsing(function ($record) {
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
                    ->label('Ver Detalle')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->size('sm')
                    ->modalHeading(fn($record) => "📋 Pagos de {$record->nombre_completo}")
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
            ->paginated(false)
            ->striped()
            ->defaultSort(null);
    }
    
    /**
     * No visible para profesores
     */
    public static function canView(): bool
    {
        return !auth()->user()?->esProfesor();
    }
}
