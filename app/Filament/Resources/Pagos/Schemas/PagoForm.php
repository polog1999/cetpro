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
                TextInput::make('codigo')
                    ->required(),
                Select::make('matricula_id')
                    ->relationship('matricula', 'id')
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
                TextInput::make('metodo_pago'),
                DatePicker::make('fecha_pago'),
                TextInput::make('evidencia'),
            ]);
    }
}
