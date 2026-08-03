<?php

namespace App\Filament\Resources\Programas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use App\Enums\TipoPrograma;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Get;

class ProgramaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('tipo_programa')
                    ->label('Tipo de programa')
                    ->options(TipoPrograma::class)
                    ->live()
                    ->native(false)
                    ->required(),

                TextInput::make('nombre_programa')
                    ->label('Nombre')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('duracion')
                    ->label('Duración en meses')
                    ->numeric()
                    ->integer(),

                TextInput::make('num_cursos')
                    ->label('Número de cursos')
                    ->numeric()
                    ->integer(),
                TextInput::make('creditos')
                    ->label('Créditos')
                    ->numeric()
                    ->integer()
                    ->visible(fn(Get $get) => $get('tipo_programa') == TipoPrograma::FORMACION_CONTINUA)
                    ->nullable(),
                TextInput::make('horas')
                    ->label('Horas')
                    ->numeric()
                    ->integer()
                    ->visible(fn(Get $get) => $get('tipo_programa') == TipoPrograma::FORMACION_CONTINUA)
                    ->nullable(),
                Select::make('id_especialidad')
                    ->label('Especialidad')
                    ->relationship('especialidad', 'nombre_especialidad')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }
}
