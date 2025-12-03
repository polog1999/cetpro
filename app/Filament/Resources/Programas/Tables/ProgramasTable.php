<?php

namespace App\Filament\Resources\Programas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Programa;
use Filament\Actions\Action;
use App\Filament\Resources\Programas\ProgramaResource;
use App\Enums\TipoPrograma;

class ProgramasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre_programa')
                    ->label('Nombre')
                    ->searchable(),

                TextColumn::make('tipo_programa')
                    ->label('Tipo de programa')
                    ->badge()
                    ->formatStateUsing(
                        fn (?TipoPrograma $state) => $state?->getLabel()
                    )
                    ->color(
                        fn (?TipoPrograma $state) => $state?->getColor()
                    ),

                TextColumn::make('duracion')
                    ->label('Duración (meses)')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('num_cursos')
                    ->label('Número de cursos')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('especialidad.nombre_especialidad')
                    ->label('Especialidad')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('horarios_count')
                    ->label('Horarios')
                    ->counts('horarios')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('matriculas_count')
                    ->label('Total alumnos')
                    ->getStateUsing(function (Programa $record): int {
                        return $record->horarios()
                            ->withCount('matriculas')
                            ->get()
                            ->sum('matriculas_count');
                    })
                    ->badge()
                    ->color('success')
                    ->sortable(),

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

                Action::make('agregarCursos')
                    ->label('Agregar cursos')
                    ->icon('heroicon-m-plus')
                    ->button()
                    ->url(fn (Programa $record): string =>
                        ProgramaResource::getUrl('view', ['record' => $record])
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
