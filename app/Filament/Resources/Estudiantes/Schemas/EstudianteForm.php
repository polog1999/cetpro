<?php

namespace App\Filament\Resources\Estudiantes\Schemas;

use App\Enums\DistritoLima;
use App\Enums\EstadoCivil;
use App\Enums\GradoInstruccion;
use App\Enums\Provincia;
use App\Enums\TipoDocumento;
use App\Enums\TipoGenero;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use App\Models\Apoderado;

class EstudianteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipo_documento')
                    ->options(TipoDocumento::class)
                    ->required(),
                TextInput::make('nro_documento')
                    ->required(),
                TextInput::make('nombres')
                    ->required(),
                TextInput::make('apellido_paterno')
                    ->required(),
                TextInput::make('apellido_materno')
                    ->required(),
                Select::make('genero')
                    ->options(TipoGenero::class),
                Select::make('estado_civil')
                    ->options(EstadoCivil::class),
                DatePicker::make('fecha_nacimiento'),
                TextInput::make('telefono')
                    ->tel(),
                TextInput::make('direccion'),
                TextInput::make('email')
                    ->label('Email')
                    ->email(),
                // 🔹 AQUÍ el cambio importante
                Select::make('apoderado_id')
                    ->label('Apoderado')
                    ->relationship(
                        name: 'apoderado',
                        titleAttribute: 'nombres', // columna real de la tabla
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (Apoderado $record) => $record->nombre_completo
                    )
                    ->searchable(['nombres', 'apellido_paterno', 'apellido_materno'])
                    ->preload(),
                Select::make('grado_instruccion')
                    ->options(GradoInstruccion::class),
                Select::make('provincia')
                    ->options(Provincia::class)
                    ->default('Lima')
                    ->required(),
                Select::make('distrito')
                    ->options(DistritoLima::class),
            ]);
    }
}
