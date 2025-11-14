<?php

namespace App\Filament\Resources\Programas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ProgramaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre_programa')
                    ->label('Nombre')
                    ->required(),

                TextInput::make('duracion')
                    ->label('Duración en meses')
                    ->numeric()
                    ->integer(),

                TextInput::make('num_componentes')
                    ->label('Número de cursos')
                    ->numeric()
                    ->integer(),

                Select::make('id_rubro')
                    ->label('Rubro')
                    ->relationship('rubro', 'nombre_rubro') // usa la relación del modelo Programa
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }
}


