<?php

namespace App\Filament\Resources\Estudiantes\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NotasRelationManager extends RelationManager
{
    protected static string $relationship = 'notas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('curso_id')
                    ->label('Curso')
                    ->relationship('curso', 'nombre_curso')
                    ->required(),
                Forms\Components\Select::make('tipo_evaluacion')
                    ->label('Tipo de Evaluación')
                    ->options(\App\Enums\TipoEvaluacion::class)
                    ->required(),
                Forms\Components\TextInput::make('periodo')
                    ->label('Período'),
                Forms\Components\TextInput::make('nota')
                    ->label('Nota')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(20)
                    ->required(),
                Forms\Components\DatePicker::make('fecha_evaluacion')
                    ->label('Fecha de Evaluación')
                    ->required(),
                Forms\Components\Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('fecha_evaluacion')
            ->columns([
                TextColumn::make('curso.nombre_curso')
                    ->label('Curso')
                    ->searchable(),
                TextColumn::make('tipo_evaluacion')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('nota')
                    ->label('Nota')
                    ->badge()
                    ->color(fn ($state) => $state >= 11 ? 'success' : 'danger'),
                TextColumn::make('docente.nombre_completo')
                    ->label('Profesor'),
                TextColumn::make('fecha_evaluacion')
                    ->label('Fecha')
                    ->date('d/m/Y'),
            ])
            ->filters([
                SelectFilter::make('tipo_evaluacion')
                    ->options(\App\Enums\TipoEvaluacion::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn () => auth()->user()?->role?->es_admin ?? false),  
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => auth()->user()?->role?->es_admin ?? false),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()?->role?->es_admin ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
