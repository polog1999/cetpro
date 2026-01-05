<?php

namespace App\Filament\Resources\Notas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;

class NotasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('matricula.codigo_inscripcion')
                    ->label('Código de matrícula')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('curso.nombre_curso')
                    ->label('Curso')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('docente.nombres')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('nota_numerica')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('nota_letra'),
                ImageColumn::make('pdf_calificacion')
                    ->label('Calificación')
                    ->circular()
                    ->stacked(),
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
                EditAction::make()
                    ->visible(fn () => !auth()->user()?->esProfesor()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => !auth()->user()?->esProfesor()),
                ]),
            ]);
    }
}
