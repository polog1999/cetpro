<?php

namespace App\Filament\Resources\Empleados\Tables;

use App\Filament\Traits\PreventDeleteWithDependencies;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

class EmpleadosTable
{
    use PreventDeleteWithDependencies;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('apellido_paterno')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('apellido_materno')
                    ->searchable(),
                TextColumn::make('correo')
                    ->searchable()
                    ->icon('heroicon-o-envelope'),
                TextColumn::make('celular')
                    ->searchable()
                    ->icon('heroicon-o-phone'),
                    
                // Indicador visual si tiene usuario
                IconColumn::make('usuario')
                    ->label('Usuario')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => $record->usuario()->exists()),
                    
                TextColumn::make('tipo_documento')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('num_documento')
                    ->label('N° Documento')
                    ->searchable(),
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
                EditAction::make(),
                DeleteAction::make()
                    ->before(fn (DeleteAction $action, $record) => 
                        self::preventDeleteWithDependencies(
                            $action,
                            $record,
                            'usuario',
                            'usuario de sistema'
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
                                'usuario',
                                'usuario',
                                'nombre'
                            )
                        ),
                ]),
            ]);
    }
}
