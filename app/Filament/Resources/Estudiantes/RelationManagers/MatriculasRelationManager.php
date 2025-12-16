<?php

namespace App\Filament\Resources\Estudiantes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Matricula;
use App\Models\Pago;
use Filament\Actions\Action;

class MatriculasRelationManager extends RelationManager
{
    protected static string $relationship = 'matriculas';

    protected static ?string $title = 'Historial de Matrículas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Read-only generally, as detailed editing should happen in MatriculaResource
                TextInput::make('codigo_inscripcion')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('codigo_inscripcion')
            ->columns([
                Tables\Columns\TextColumn::make('codigo_inscripcion')
                    ->label('Código')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Inscripción')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo_matricula')
                    ->badge(),

                Tables\Columns\TextColumn::make('horario.programa_curso_modulo')
                    ->label('Programa / Curso')
                    ->state(function (Matricula $record) {
                        return match($record->tipo_matricula) {
                            \App\Enums\TipoMatricula::PROGRAMA => $record->horario?->programa?->nombre_programa,
                            \App\Enums\TipoMatricula::CURSO => $record->curso?->nombre_curso,
                            default => 'N/A'
                        };
                    })
                    ->description(fn (Matricula $record) => $record->horario?->descripcion_horario),

                Tables\Columns\TextColumn::make('estado')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Action to view payments
                Action::make('ver_pagos')
                    ->label('Ver Pagos')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->modalHeading(fn (Matricula $record) => "Pagos - Matrícula {$record->codigo_inscripcion}")
                    ->modalContent(function (Matricula $record) {
                        $pagos = $record->cronograma?->pagos()->orderBy('nro_cuota')->get() ?? collect();
                        
                        return view('filament.resources.estudiantes.components.pagos-modal', [
                            'pagos' => $pagos,
                            'cronograma' => $record->cronograma
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),

                Action::make('ir_a_matricula')
                    ->label('Gestionar')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Matricula $record) => \App\Filament\Resources\Matriculas\MatriculaResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }
}
