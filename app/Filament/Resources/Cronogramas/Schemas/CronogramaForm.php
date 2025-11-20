<?php

namespace App\Filament\Resources\Cronogramas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CronogramaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('matricula_id')
                    ->relationship('matricula', 'id')
                    ->required(),
                TextInput::make('num_cuotas')
                    ->required()
                    ->numeric(),
                TextInput::make('monto_total')
                    ->required()
                    ->numeric(),
            ]);
    }
}
