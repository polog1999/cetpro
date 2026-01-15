<?php

namespace App\Filament\Resources\Pagos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PagoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cronograma_id')
                    ->relationship('cronograma', 'id')
                    ->required(),
                TextInput::make('nro_cuota')
                    ->required()
                    ->numeric(),
                TextInput::make('monto')
                    ->required()
                    ->numeric(),
                TextInput::make('estado')
                    ->label('Estado Oracle')
                    ->disabled()
                    ->helperText('El estado viene de Oracle'),
                DatePicker::make('fecha_vencimiento')
                    ->required(),
                DatePicker::make('fecha_pago'),
                TextInput::make('metodo_pago'),
                TextInput::make('evidencia_path'),
                TextInput::make('num_liquidacion'),
                DatePicker::make('fecha_liquidacion'),
            ]);
    }
}
