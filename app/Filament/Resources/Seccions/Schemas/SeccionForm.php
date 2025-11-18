<?php

namespace App\Filament\Resources\Seccions\Schemas;

use App\Enums\Modalidad;
use App\Enums\Turno;
use App\Enums\TipoPrograma;
use App\Models\Programa;
use App\Models\Docente;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

use App\Enums\TipoDocumento;


use Filament\Actions\Action;


class SeccionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // TIPO DE PROGRAMA (FILTRO, NO SE GUARDA EN BD)
                ToggleButtons::make('tipo_programa')
                    ->label('Tipo de programa')
                    ->options([
                        TipoPrograma::PROGRAMA_ESTUDIO->value   => 'Programa de estudio',
                        TipoPrograma::FORMACION_CONTINUA->value => 'Programa de formación continua',
                    ])
                    ->inline()
                    ->required()
                    ->live()
                    ->dehydrated(false) // 👈 solo se usa para filtrar, no se guarda
                    ->afterStateUpdated(function (Set $set) {
                        // cuando cambia el tipo de programa, limpiamos el programa seleccionado
                        $set('id_programa', null);
                    }),

                // PROGRAMA (se filtra por tipo_programa)
                Select::make('id_programa')
                    ->label('Programa')
                    ->options(function (Get $get) {
                        $tipo = $get('tipo_programa');

                        if (! $tipo) {
                            return [];
                        }

                        return Programa::query()
                            ->where('tipo_programa', $tipo) // columna enum en programas
                            ->orderBy('nombre_programa')
                            ->pluck('nombre_programa', 'id_programa')
                            ->toArray();
                    })
                    ->placeholder('Seleccione un programa')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (Get $get): bool => ! $get('tipo_programa')),

                // TURNO
                Select::make('turno')
                    ->label('Turno')
                    ->options(Turno::class)
                    ->required(),

                // MODALIDAD
                Select::make('modalidad')
                    ->label('Modalidad')
                    ->options(Modalidad::class)
                    ->required(),

                // DÍAS
                ToggleButtons::make('dias')
                    ->label('Días de estudio')
                    ->options([
                        'LUN' => 'Lunes',
                        'MAR' => 'Martes',
                        'MIE' => 'Miércoles',
                        'JUE' => 'Jueves',
                        'VIE' => 'Viernes',
                        'SAB' => 'Sábado',
                        'DOM' => 'Domingo',
                    ])
                    ->multiple()
                    ->inline()
                    ->required(),

                Select::make('id_docente')
    ->label('Docente')
    ->options(function () {
        return Docente::query()
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->get()
            ->mapWithKeys(fn (Docente $docente) => [
                $docente->id => $docente->nombre_completo,
            ])
            ->toArray();
    })
    ->searchable()
    ->preload()
    ->required()
    ->getOptionLabelUsing(fn ($value): ?string =>
        Docente::find($value)?->nombre_completo
    )

    // 👉 Formulario para crear docentes al vuelo
    ->createOptionForm([
        Select::make('tipo_documento')
            ->label('Tipo de documento')
            ->options(TipoDocumento::class)   // enum con HasLabel
            ->required(),

        TextInput::make('nro_documento')
            ->label('Nro. de documento')
            ->required(),

        TextInput::make('nombres')
            ->label('Nombres')
            ->required(),

        TextInput::make('apellido_paterno')
            ->label('Apellido paterno')
            ->required(),

        TextInput::make('apellido_materno')
            ->label('Apellido materno')
            ->required(),
    ])

    ->createOptionAction(function (Action $action) {
        return $action
            ->label('Registrar docente')
            ->modalHeading('Registrar nuevo docente')
            ->modalSubmitActionLabel('Guardar docente');
    })

    ->createOptionUsing(function (array $data) {
        return Docente::create($data)->getKey();
    }),




                // HORA DE INICIO
                TimePicker::make('hora_inicio')
                    ->label('Hora de inicio')
                    ->seconds(false)
                    ->required(),

                // HORA DE FIN
                TimePicker::make('hora_fin')
                    ->label('Hora de fin')
                    ->seconds(false)
                    ->required(),

                
                // AULA
                TextInput::make('aula')
                    ->label('Aula')
                    ->maxLength(255)
                    ->nullable(),
            ]);
    }
}
