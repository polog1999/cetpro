<?php

namespace App\Filament\Resources\Matriculas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

use App\Models\Estudiante;
use App\Models\OfertaAcademica;
use App\Enums\TipoDocumento;
use App\Enums\EstadoMatricula;

use Filament\Infolists\Components\TextEntry;
use App\Models\Matricula;

class MatriculaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de matrícula')
                    ->schema([

                        Select::make('estudiante_id')
                            ->relationship('estudiante', 'nombres')
                            ->label('Estudiante')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::actualizarCodigoPreview($get, $set);
                            })
                            ->createOptionForm(self::getEstudianteFormSchema())
                            ->createOptionAction(function (Action $action) {
                                return $action
                                    ->modalHeading('Crear Nuevo Estudiante')
                                    ->modalSubmitActionLabel('Crear Estudiante')
                                    ->modalWidth('lg');
                            }),

                        Select::make('oferta_academica_id')
                            ->relationship('ofertaAcademica', 'id_oferta')
                            ->label('Oferta académica')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::actualizarCodigoPreview($get, $set);
                            }),

                        ToggleButtons::make('estado')
                            ->options(EstadoMatricula::class)
                            ->inline()
                            ->required(),

                        TextInput::make('codigo')
                            ->label('Código de matrícula')
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2)
                    ->columnSpan(['lg' => fn (?Matricula $record) => $record === null ? 3 : 2]),

                Section::make()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Fecha de creación')
                            ->state(fn (Matricula $record): ?string => $record->created_at?->diffForHumans()),

                        TextEntry::make('updated_at')
                            ->label('Fecha de actualización')
                            ->state(fn (Matricula $record): ?string => $record->updated_at?->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Matricula $record) => $record === null),
            ])
            ->columns(3);
    }

    private static function actualizarCodigoPreview(Get $get, Set $set): void
    {
        $estudianteId = $get('estudiante_id');
        $ofertaId     = $get('oferta_academica_id');

        if (blank($estudianteId) || blank($ofertaId)) {
            $set('codigo', null);
            return;
        }

        $estudiante = Estudiante::find($estudianteId);
        $oferta     = OfertaAcademica::find($ofertaId);

        if (!$estudiante || !$oferta) {
            $set('codigo', 'Error: Faltan datos...');
            return;
        }

        $dni = $estudiante->nro_documento ?? 'SIN-DNI';

        // Mismo formato que en el boot() del modelo:
        // OF-0001-12345678
        $codigoOferta  = 'OF-' . str_pad($oferta->id_oferta, 4, '0', STR_PAD_LEFT);
        $codigoPreview = "{$codigoOferta}-{$dni}";

        $set('codigo', $codigoPreview);
    }

    private static function getEstudianteFormSchema(): array
    {
        return [
            Select::make('tipo_documento')
                ->options(TipoDocumento::class)
                ->required(),

            TextInput::make('nro_documento')
                ->required()
                ->maxLength(25),

            DatePicker::make('fecha_nacimiento')
                ->label('Fecha de Nacimiento')
                ->required(),

            TextInput::make('nombres')
                ->required(),

            TextInput::make('apellido_paterno')
                ->required(),

            TextInput::make('apellido_materno')
                ->required(),
        ];
    }
}
