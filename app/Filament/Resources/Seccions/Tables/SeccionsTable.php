<?php

namespace App\Filament\Resources\Seccions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Enums\Turno;
use App\Enums\Modalidad;

class SeccionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('programa.nombre_programa')
                    ->label('Programa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('docente.nombre_completo')
                    ->label('Docente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('turno')
                    ->label('Turno')
                    ->badge()
                    ->formatStateUsing(fn (?Turno $state) => $state?->getLabel())
                    ->color(fn (?Turno $state) => 'primary'),

                TextColumn::make('modalidad')
                    ->label('Modalidad')
                    ->badge()
                    ->formatStateUsing(fn (?Modalidad $state) => $state?->getLabel())
                    ->color(fn (?Modalidad $state) => $state?->getColor()),

                TextColumn::make('dias')
                    ->label('Días')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return implode(', ', $state);
                        }

                        return $state;
                    }),

                TextColumn::make('horario')
                    ->label('Horario'),

                TextColumn::make('aula')
                    ->label('Aula')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Creado')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Actualizado')
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
