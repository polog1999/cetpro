<?php

namespace App\Filament\Resources\Pagos\Schemas;

use App\Enums\EstadoPago;
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
                TextInput::make('codigo')
                    ->required(),
                TextInput::make('monto')
                    ->required()
                    ->numeric(),
                Select::make('estado')
                    ->options(EstadoPago::class)
                    ->default('pendiente')
                    ->required(),
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
