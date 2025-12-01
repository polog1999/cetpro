<?php

namespace App\Filament\Resources\Apoderados\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

use App\Enums\TipoDocumento;

class ApoderadoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo_documento')
                    ->options(TipoDocumento::class),
                TextInput::make('nro_documento'),
                TextInput::make('apellido_paterno'),
                TextInput::make('apellido_materno'),
                TextInput::make('nombres'),
                TextInput::make('telefono')
                    ->tel(),
            ]);
    }
}
