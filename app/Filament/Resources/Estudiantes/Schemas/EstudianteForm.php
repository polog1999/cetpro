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
use Filament\Forms\Get;
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
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Select $component) => $component
                        ->getContainer()
                        ->getComponent('nro_documento_component')
                        ->state(null)
                    ),
                TextInput::make('nro_documento')
                    ->key('nro_documento_component')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Este número de documento ya está registrado para otro estudiante.',
                    ])
                    ->maxLength(function ($get) {
                        $tipo = $get('tipo_documento');
                        if (! $tipo instanceof TipoDocumento) {
                            $tipo = TipoDocumento::tryFrom($tipo);
                        }
                        return $tipo?->getMaxLength() ?? 8;
                    })
                    ->extraInputAttributes(function ($get) {
                        $tipo = $get('tipo_documento');
                        if (! $tipo instanceof TipoDocumento) {
                            $tipo = TipoDocumento::tryFrom($tipo);
                        }
                        $isNumeric = $tipo?->isNumeric() ?? true;
                        $maxLength = $tipo?->getMaxLength() ?? 8;
                        
                        $regex = $isNumeric ? '/[^0-9]/g' : '/[^a-zA-Z0-9]/g';
                        
                        return [
                            'oninput' => "this.value = this.value.replace($regex, '').slice(0, $maxLength)",
                        ];
                    }),
                TextInput::make('nombres')
                    ->required()
                    ->regex('/^[\pL\s]+$/u')
                    ->validationMessages([
                        'regex' => 'Solo se permiten letras y espacios.',
                    ])
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),
                TextInput::make('apellido_paterno')
                    ->required()
                    ->regex('/^[\pL\s]+$/u')
                    ->validationMessages([
                        'regex' => 'Solo se permiten letras y espacios.',
                    ])
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),
                TextInput::make('apellido_materno')
                    ->required()
                    ->regex('/^[\pL\s]+$/u')
                    ->validationMessages([
                        'regex' => 'Solo se permiten letras y espacios.',
                    ])
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),
                Select::make('genero')
                    ->options(TipoGenero::class),
                Select::make('estado_civil')
                    ->options(EstadoCivil::class),
                DatePicker::make('fecha_nacimiento'),
                TextInput::make('telefono')
                    ->tel()
                    ->numeric()
                    ->maxLength(9)
                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"]),
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
                    ->options(DistritoLima::class)
                    ->required(),
            ]);
    }
}
