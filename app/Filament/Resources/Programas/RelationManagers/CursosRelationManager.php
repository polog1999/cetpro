<?php

namespace App\Filament\Resources\Programas\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action as TableAction;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use App\Enums\TipoPrograma;
use App\Models\Unidad;
use Filament\Notifications\Notification;


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

    /**
     * Verifica si el programa es de tipo PROGRAMA_ESTUDIO.
     */
    protected function esProgramaEstudio(): bool
    {
        return $this->getOwnerRecord()?->tipo_programa === TipoPrograma::PROGRAMA_ESTUDIO;
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
        $esProgramaEstudio = $this->esProgramaEstudio();

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

                // Mostrar contador de unidades solo para Programas de Estudio
                Tables\Columns\TextColumn::make('unidades_count')
                    ->label('Unidades')
                    ->counts('unidades')
                    ->badge()
                    ->color('info')
                    ->visible($esProgramaEstudio),
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
                // Acción para gestionar unidades (solo en Programas de Estudio)
                TableAction::make('unidades')
                    ->label('Unidades')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->visible($esProgramaEstudio)
                    ->modalHeading(fn ($record) => "Unidades de: {$record->nombre_curso}")
                    ->modalWidth('4xl')
                    ->form(function ($record) {
                        return [
                            Forms\Components\Repeater::make('unidades_data')
                                ->label('Unidades del Módulo')
                                ->schema([
                                    Forms\Components\TextInput::make('nombre_unidad')
                                        ->label('Nombre de la Unidad')
                                        ->required()
                                        ->maxLength(150)
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('duracion')
                                        ->label('Duración (hrs)')
                                        ->numeric()
                                        ->integer()
                                        ->nullable(),

                                    Forms\Components\TextInput::make('orden')
                                        ->label('Orden')
                                        ->numeric()
                                        ->integer()
                                        ->default(1)
                                        ->required(),

                                    Forms\Components\Textarea::make('descripcion')
                                        ->label('Descripción')
                                        ->rows(2)
                                        ->columnSpanFull()
                                        ->nullable(),
                                ])
                                ->columns(4)
                                ->defaultItems(0)
                                ->addActionLabel('Agregar Unidad')
                                ->reorderable()
                                ->orderColumn('orden')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['nombre_unidad'] ?? 'Nueva Unidad')
                                ->default(function () use ($record) {
                                    return $record->unidades->map(fn ($unidad) => [
                                        'id' => $unidad->id_unidad,
                                        'nombre_unidad' => $unidad->nombre_unidad,
                                        'duracion' => $unidad->duracion,
                                        'orden' => $unidad->orden,
                                        'descripcion' => $unidad->descripcion,
                                    ])->toArray();
                                }),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        // Eliminar unidades existentes y recrear
                        $record->unidades()->delete();
                        
                        $orden = 1;
                        foreach ($data['unidades_data'] ?? [] as $unidadData) {
                            $record->unidades()->create([
                                'nombre_unidad' => $unidadData['nombre_unidad'],
                                'duracion' => $unidadData['duracion'] ?? null,
                                'orden' => $unidadData['orden'] ?? $orden,
                                'descripcion' => $unidadData['descripcion'] ?? null,
                            ]);
                            $orden++;
                        }
                        
                        Notification::make()
                            ->title('Unidades actualizadas')
                            ->success()
                            ->send();
                    }),

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
