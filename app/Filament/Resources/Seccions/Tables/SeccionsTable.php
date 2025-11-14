<?php

namespace App\Filament\Resources\Seccions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SeccionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipo_oferta')
                    ->searchable(),
                TextColumn::make('id_programa')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('id_curso')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('turno')
                    ->searchable(),
                TextColumn::make('dias')
                    ->searchable(),
                TextColumn::make('horario')
                    ->searchable(),
                TextColumn::make('docente_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('modalidad')
                    ->searchable(),
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
