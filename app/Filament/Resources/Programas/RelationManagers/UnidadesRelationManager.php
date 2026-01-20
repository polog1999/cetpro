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

/**
 * RelationManager para gestionar unidades dentro de módulos.
 * 
 * Solo se muestra para cursos que pertenecen a programas de tipo PROGRAMA_ESTUDIO.
 */
class UnidadesRelationManager extends RelationManager
{
    protected static string $relationship = 'unidades';

    protected static ?string $recordTitleAttribute = 'nombre_unidad';

    public function isReadOnly(): bool
    {
        return false;
    }

    /**
     * Solo mostrar este RelationManager si el curso pertenece a un Programa de Estudio.
     */
    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        // Verificar si el programa padre es de tipo PROGRAMA_ESTUDIO
        $programa = $ownerRecord->programa;
        
        if (!$programa) {
            return false;
        }
        
        return $programa->tipo_programa === TipoPrograma::PROGRAMA_ESTUDIO;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('nombre_unidad')
                ->label('Nombre de la Unidad')
                ->required()
                ->maxLength(150),

            Forms\Components\TextInput::make('duracion')
                ->label('Duración (horas)')
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
                ->rows(3)
                ->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Unidades del Módulo')
            ->columns([
                Tables\Columns\TextColumn::make('orden')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('nombre_unidad')
                    ->label('Nombre de la Unidad')
                    ->searchable(),

                Tables\Columns\TextColumn::make('duracion')
                    ->label('Duración (hrs)')
                    ->suffix(' hrs')
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(50)
                    ->placeholder('Sin descripción'),
            ])
            ->defaultSort('orden')
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar Unidad')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Auto-asignar el siguiente orden si no se especifica
                        if (empty($data['orden'])) {
                            $maxOrden = $this->getOwnerRecord()->unidades()->max('orden') ?? 0;
                            $data['orden'] = $maxOrden + 1;
                        }
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->label('Editar'),
                DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->reorderable('orden');
    }
}
