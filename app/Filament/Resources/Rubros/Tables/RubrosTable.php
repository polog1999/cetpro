<?php

namespace App\Filament\Resources\Rubros\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RubrosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre_rubro')
                    ->searchable(),
                TextColumn::make('costo_mensual')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('num_resolucion')
                    ->searchable(),
                TextColumn::make('fecha_registro')
                    ->date()
                    ->sortable(),
                TextColumn::make('fecha_inicio_vigencia')
                    ->date()
                    ->sortable(),
                TextColumn::make('fecha_fin_vigencia')
                    ->date()
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
