<?php

namespace App\Filament\Resources\Matriculas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MatriculasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo_inscripcion')
                    ->label('Código inscripción')
                    ->searchable()
                    ->sortable(),

                // ESTUDIANTE: nombre completo (relación estudiante)
                TextColumn::make('estudiante.nombre_completo')
                    ->label('Estudiante')
                    ->sortable()
                    ->searchable(),

                // ESTADO (enum)
                TextColumn::make('estado')
                    ->label('Estado')
                    ->sortable()
                    ->searchable(),

                // TIPO DE MATRÍCULA (enum)
                TextColumn::make('tipo_matricula')
                    ->label('Tipo de matrícula')
                    ->sortable()
                    ->searchable(),

                // SECCIÓN: puedes mostrar algo representativo
                TextColumn::make('seccion.programa.nombre_programa')
                    ->label('Programa')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('seccion.id_seccion')
                    ->label('Sección')
                    ->sortable()
                    ->toggleable(),

                // CURSO: nombre del curso (relación curso)
                TextColumn::make('curso.nombre_curso')
                    ->label('Curso')
                    ->sortable()
                    ->toggleable(),

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
