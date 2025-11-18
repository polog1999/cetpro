<?php

namespace App\Filament\Resources\Especialidads\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EspecialidadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre_especialidad')
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
