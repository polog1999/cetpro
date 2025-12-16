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
use App\Enums\TipoPrograma;

class CursosRelationManager extends RelationManager
{
    protected static string $relationship = 'cursos';

    protected static ?string $recordTitleAttribute = 'nombre_curso';

    public function isReadOnly(): bool
    {
        return false;
    }

    /**
     * Obtiene el label dinámico según el tipo de programa.
     * Programa = Módulo(s), Formación Continua = Curso(s)
     */
    protected function getItemLabel(bool $plural = false): string
    {
        $programa = $this->getOwnerRecord();
        $isPrograma = $programa?->tipo_programa === TipoPrograma::PROGRAMA_ESTUDIO;
        
        if ($plural) {
            return $isPrograma ? 'Módulos' : 'Cursos';
        }
        
        return $isPrograma ? 'Módulo' : 'Curso';
    }

    public function form(Schema $schema): Schema
    {
        $label = $this->getItemLabel();
        
        return $schema->components([
            Forms\Components\TextInput::make('nombre_curso')
                ->label("Nombre del {$label}")
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
        $label = $this->getItemLabel();
        $labelPlural = $this->getItemLabel(true);

        return $table
            ->heading($labelPlural)
            ->columns([
                Tables\Columns\TextColumn::make('nombre_curso')
                    ->label("Nombre del {$label}")
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
                    ->label("Agregar {$label}")
                    ->disabled(function () {
                        $programa = $this->getOwnerRecord();
                        $cursosActuales = $programa->cursos()->count();
                        $numeroMaximoCursos = $programa->num_cursos;
                        
                        return $cursosActuales >= $numeroMaximoCursos;
                    })
                    ->tooltip(function () use ($labelPlural) {
                        $programa = $this->getOwnerRecord();
                        $cursosActuales = $programa->cursos()->count();
                        $numeroMaximoCursos = $programa->num_cursos;
                        
                        if ($cursosActuales >= $numeroMaximoCursos) {
                            return "Límite alcanzado: {$cursosActuales}/{$numeroMaximoCursos} {$labelPlural}";
                        }
                        
                        return "{$labelPlural}: {$cursosActuales}/{$numeroMaximoCursos}";
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

