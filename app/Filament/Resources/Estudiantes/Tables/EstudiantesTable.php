<?php

namespace App\Filament\Resources\Estudiantes\Tables;

use App\Filament\Traits\PreventDeleteWithDependencies;
use App\Services\OracleTusneService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class EstudiantesTable
{
    use PreventDeleteWithDependencies;
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nro_documento')
                    ->searchable(),
                
                // Columna virtual - Código de Contribuyente desde Oracle
                TextColumn::make('codigo_contribuyente')
                    ->label('Cód. Contribuyente')
                    ->getStateUsing(function ($record): string {
                        // Cache por 1 hora para evitar consultas repetidas
                        return Cache::remember(
                            "contribuyente_dni_{$record->nro_documento}",
                            3600,
                            function () use ($record) {
                                try {
                                    $oracle = app(OracleTusneService::class);
                                    // Usar el nuevo método que retorna solo el código más reciente
                                    $codigoReciente = $oracle->obtenerCodigoContribuyenteMasReciente($record->nro_documento);
                                    
                                    if ($codigoReciente && !empty($codigoReciente->CODIGO)) {
                                        return trim($codigoReciente->CODIGO);
                                    }
                                    
                                    return 'Sin número';
                                } catch (\Exception $e) {
                                    return 'Error';
                                }
                            }
                        );
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sin número' => 'warning',
                        'Error' => 'danger',
                        default => 'success',
                    })
                    ->copyable()
                    ->copyMessage('Código copiado'),
                    
                TextColumn::make('nombres')
                    ->searchable(),
                TextColumn::make('apellido_paterno')
                    ->searchable(),
                TextColumn::make('apellido_materno')
                    ->searchable(),
                TextColumn::make('genero')
                    ->searchable(),
                TextColumn::make('estado_civil')
                    ->searchable(),
                TextColumn::make('fecha_nacimiento')
                    ->date()
                    ->sortable(),
                TextColumn::make('telefono')
                    ->searchable(),
                TextColumn::make('direccion')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                    
                // Columna de matrículas
                TextColumn::make('matriculas_count')
                    ->label('Matrículas')
                    ->counts('matriculas')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('apoderado.nombre_completo'),
                TextColumn::make('grado_instruccion')
                    ->searchable(),
                TextColumn::make('provincia')
                    ->searchable(),
                TextColumn::make('distrito')
                    ->searchable(),
            ])
            ->filters([
                Filter::make('nro_documento')
                    ->label('DNI')
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $value): Builder => $query->where('nro_documento', 'like', "%{$value}%")
                        )
                    )
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['value']) {
                            return null;
                        }
                        return 'DNI: ' . $data['value'];
                    })
                    ->form([
                        \Filament\Forms\Components\TextInput::make('value')
                            ->label('DNI')
                            ->placeholder('Ingrese DNI'),
                    ]),

                Filter::make('nombres')
                    ->label('Nombre')
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $value): Builder => $query->where('nombres', 'like', "%{$value}%")
                        )
                    )
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['value']) {
                            return null;
                        }
                        return 'Nombre: ' . $data['value'];
                    })
                    ->form([
                        \Filament\Forms\Components\TextInput::make('value')
                            ->label('Nombre')
                            ->placeholder('Ingrese nombre'),
                    ]),

                Filter::make('apellido_paterno')
                    ->label('Apellido Paterno')
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $value): Builder => $query->where('apellido_paterno', 'like', "%{$value}%")
                        )
                    )
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['value']) {
                            return null;
                        }
                        return 'Apellido Paterno: ' . $data['value'];
                    })
                    ->form([
                        \Filament\Forms\Components\TextInput::make('value')
                            ->label('Apellido Paterno')
                            ->placeholder('Ingrese apellido paterno'),
                    ]),

                Filter::make('apellido_materno')
                    ->label('Apellido Materno')
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $value): Builder => $query->where('apellido_materno', 'like', "%{$value}%")
                        )
                    )
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['value']) {
                            return null;
                        }
                        return 'Apellido Materno: ' . $data['value'];
                    })
                    ->form([
                        \Filament\Forms\Components\TextInput::make('value')
                            ->label('Apellido Materno')
                            ->placeholder('Ingrese apellido materno'),
                    ]),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                EditAction::make(),
                
                \Filament\Actions\Action::make('ver_pagos')
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
                
                DeleteAction::make()
                    ->before(fn (DeleteAction $action, $record) => 
                        self::preventDeleteWithDependencies(
                            $action,
                            $record,
                            'matriculas',
                            'matrícula(s)'
                        )
                    ),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(fn (DeleteBulkAction $action, $records) => 
                            self::preventBulkDeleteWithDependencies(
                                $action,
                                $records,
                                'matriculas',
                                'matrícula(s)',
                                'nombres'
                            )
                        ),
                ]),
            ]);
    }
}
