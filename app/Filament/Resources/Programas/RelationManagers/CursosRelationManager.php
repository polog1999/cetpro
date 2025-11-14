<?php

namespace App\Filament\Resources\Programas\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\HasManyRelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;

class CursosRelationManager extends RelationManager
{
    protected static string $relationship = 'cursos';

    protected static ?string $title = 'Cursos';

    protected static ?string $recordTitleAttribute = 'nombre_curso';

    // 👇 IMPORTANTE: desactivar modo solo lectura en la página "Ver"
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('nombre_curso')
                ->label('Nombre de curso')
                ->required(),

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

            Forms\Components\TextInput::make('aula')
                ->label('Aula')
                ->maxLength(255),
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

                Tables\Columns\TextColumn::make('aula')
                    ->label('Aula'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agrega los cursos'),
            ])
            ->actions([
                EditAction::make()
                    ->label('Editar'),
                DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
            DeleteBulkAction::make(),
            ]);
    }
}
