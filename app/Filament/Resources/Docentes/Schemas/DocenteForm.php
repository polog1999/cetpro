<?php

namespace App\Filament\Resources\Docentes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DocenteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('tipo_documento')
                    ->required(),
                TextInput::make('nro_documento')
                    ->required(),
                TextInput::make('nombres')
                    ->required(),
                TextInput::make('apellido_paterno')
                    ->required(),
                TextInput::make('apellido_materno')
                    ->required(),
            ]);
    }
}
