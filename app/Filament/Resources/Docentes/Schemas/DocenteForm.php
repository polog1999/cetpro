<?php

namespace App\Filament\Resources\Docentes\Schemas;

use Filament\Forms\Components\TextInput;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

use App\Enums\TipoDocumento;

class DocenteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo_documento')
                    ->options(TipoDocumento::class)
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
