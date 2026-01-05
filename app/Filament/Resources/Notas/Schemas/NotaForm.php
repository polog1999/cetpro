<?php

namespace App\Filament\Resources\Notas\Schemas;

use App\Enums\TipoEvaluacion;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class NotaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('matricula_id')
                    ->relationship('matricula', 'id')
                    ->required(),
                Select::make('curso_id')
                    ->relationship('curso', 'id_curso')
                    ->required(),
                Select::make('docente_id')
                    ->relationship('docente', 'id')
                    ->required(),
                Select::make('tipo_evaluacion')
                    ->options(TipoEvaluacion::class)
                    ->required(),
                TextInput::make('periodo'),
                TextInput::make('nota')
                    ->required()
                    ->numeric(),
                TextInput::make('nota_letra'),
                Textarea::make('observaciones')
                    ->columnSpanFull(),
                DatePicker::make('fecha_evaluacion')
                    ->required(),
            ]);
    }
}
