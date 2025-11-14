<?php

namespace App\Filament\Resources\Rubros\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RubroForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre_rubro')
                    ->required(),
                TextInput::make('costo_mensual')
                    ->required()
                    ->numeric(),
                TextInput::make('num_resolucion'),
                DatePicker::make('fecha_registro'),
                DatePicker::make('fecha_inicio_vigencia'),
                DatePicker::make('fecha_fin_vigencia'),
            ]);
    }
}
