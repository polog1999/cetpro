<?php

namespace App\Filament\Resources\Matriculas\Tables;

use App\Models\Matricula;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
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

                TextColumn::make('estudiante.nombre_completo')
                    ->label('Estudiante')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('tipo_matricula')
                    ->label('Tipo de matrícula')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('horario.programa.nombre_programa')
                    ->label('Programa')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('curso.nombre_curso')
                    ->label('Curso')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),

                // 👉 Botón para generar/descargar PDF de la ficha
                Action::make('pdf')
                    ->label('Ficha PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Matricula $record) => route('matriculas.pdf', $record))
                    ->openUrlInNewTab(),

                // 👉 Botón para generar/descargar PDF de cursos/módulos
                Action::make('cursos_pdf')
                    ->label('Cursos PDF')
                    ->icon('heroicon-o-academic-cap')
                    ->url(fn (Matricula $record) => route('matriculas.cursos-pdf', $record))
                    ->openUrlInNewTab()
                    ->color('success'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
