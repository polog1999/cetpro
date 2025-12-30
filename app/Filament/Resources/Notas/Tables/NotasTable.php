<?php

namespace App\Filament\Resources\Notas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('matricula.id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('curso.id_curso')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('docente.id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tipo_evaluacion')
                    ->searchable(),
                TextColumn::make('periodo')
                    ->searchable(),
                TextColumn::make('nota')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('nota_letra')
                    ->searchable(),
                TextColumn::make('fecha_evaluacion')
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
