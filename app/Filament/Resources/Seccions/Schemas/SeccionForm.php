<?php

namespace App\Filament\Resources\Seccions\Schemas;

use App\Enums\Modalidad;
use App\Enums\TipoOfertaAcademica;
use App\Enums\Turno;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SeccionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo_oferta')
                    ->options(TipoOfertaAcademica::class)
                    ->required(),
                TextInput::make('id_programa')
                    ->numeric(),
                TextInput::make('id_curso')
                    ->numeric(),
                Select::make('turno')
                    ->options(Turno::class),
                TextInput::make('dias'),
                TextInput::make('horario'),
                TextInput::make('docente_id')
                    ->numeric(),
                Select::make('modalidad')
                    ->options(Modalidad::class),
            ]);
    }
}
