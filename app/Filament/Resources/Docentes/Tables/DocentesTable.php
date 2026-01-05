<?php

namespace App\Filament\Resources\Docentes\Tables;

use App\Filament\Traits\PreventDeleteWithDependencies;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

class DocentesTable
{
    use PreventDeleteWithDependencies;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nro_documento')
                    ->label('N° Documento')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombres')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('apellido_paterno')
                    ->label('Apellido Paterno')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('apellido_materno')
                    ->label('Apellido Materno')
                    ->searchable(),
                    
                // Columna visual para horarios
                TextColumn::make('horarios_count')
                    ->label('Horarios')
                    ->counts('horarios')
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
            ])
            ->filters([
                //
            ])
            ->actions([
                // Sin acciones disponibles
                // Los docentes se crean automáticamente desde usuarios
                // Para editar, actualizar el empleado relacionado
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(fn (DeleteBulkAction $action, $records) => 
                            self::preventBulkDeleteWithDependencies(
                                $action,
                                $records,
                                'horarios',
                                'horario(s)',
                                'nombres'
                            )
                        ),
                ]),
            ]);
    }
}
