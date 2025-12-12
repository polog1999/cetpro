<?php

namespace App\Filament\Resources\Programas\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;

class CursosRelationManager extends RelationManager
{
    protected static string $relationship = 'cursos';

    protected static ?string $title = 'Cursos';

    protected static ?string $recordTitleAttribute = 'nombre_curso';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('nombre_curso')
                ->label('Nombre de curso')
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('duracion')
                ->label('Duración')
                ->numeric()
                ->integer()
                ->required(),

            Forms\Components\DatePicker::make('fecha_inicio')
                ->label('Fecha de inicio')
                ->required(),

            Forms\Components\DatePicker::make('fecha_termino')
                ->label('Fecha de término')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre_curso')
                    ->label('Nombre de curso')
                    ->searchable(),

                Tables\Columns\TextColumn::make('duracion')
                    ->label('Duración'),

                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('fecha_termino')
                    ->label('Fecha de término')
                    ->date('d/m/Y'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar curso')
                    ->disabled(function () {
                        $programa = $this->getOwnerRecord();
                        $cursosActuales = $programa->cursos()->count();
                        $numeroMaximoCursos = $programa->num_cursos;
                        
                        return $cursosActuales >= $numeroMaximoCursos;
                    })
                    ->tooltip(function () {
                        $programa = $this->getOwnerRecord();
                        $cursosActuales = $programa->cursos()->count();
                        $numeroMaximoCursos = $programa->num_cursos;
                        
                        if ($cursosActuales >= $numeroMaximoCursos) {
                            return "Límite alcanzado: {$cursosActuales}/{$numeroMaximoCursos} cursos";
                        }
                        
                        return "Cursos: {$cursosActuales}/{$numeroMaximoCursos}";
                    })
                    ->after(function () {
                        // Refrescar después de crear
                        $this->dispatch('$refresh');
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->label('Editar'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->after(function () {
                        // Disparar evento para refrescar el componente
                        $this->dispatch('$refresh');
                    }),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
