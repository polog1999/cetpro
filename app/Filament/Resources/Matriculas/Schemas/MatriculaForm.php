<?php

namespace App\Filament\Resources\Matriculas\Schemas;

use App\Enums\EstadoMatricula;
use App\Enums\TipoMatricula;
use App\Enums\TipoDocumento;
use App\Enums\TipoPrograma;

use App\Enums\DistritoLima;
use App\Enums\EstadoCivil;
use App\Enums\GradoInstruccion;
use App\Enums\Provincia;
use App\Enums\TipoGenero;

use App\Models\Estudiante;
use App\Models\Seccion;
use App\Models\Curso;
use App\Models\Apoderado;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class MatriculaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ----------------------------------------
                // CÓDIGO DE INSCRIPCIÓN (AUTOGENERADO)
                // ----------------------------------------
                TextInput::make('codigo_inscripcion')
                    ->label('Código de inscripción')
                    ->placeholder('Se generará automáticamente')
                    ->disabled()
                    ->dehydrated(true),

                // ----------------------------------------
                // ESTUDIANTE (SELECT + CREAR CON WIZARD)
                // ----------------------------------------
                Select::make('estudiante_id')
                    ->label('Estudiante')
                    ->relationship('estudiante', 'nombres')
                    ->getOptionLabelFromRecordUsing(
                        fn (Estudiante $record): string =>
                            trim("{$record->nombres} {$record->apellido_paterno} {$record->apellido_materno}") ?: 'Sin nombre'
                    )
                    ->searchable([
                        'nombres',
                        'apellido_paterno',
                        'apellido_materno',
                        'nro_documento',
                    ])
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Wizard::make([
                            Step::make('Estudiante')
                                ->schema([
                                    Select::make('tipo_documento')
                                        ->options(TipoDocumento::class)
                                        ->required(),

                                    TextInput::make('nro_documento')
                                        ->required()
                                        ->unique(Estudiante::class, 'nro_documento'),

                                    TextInput::make('nombres')
                                        ->required()
                                        ->columnSpanFull(),

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

                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email(),

                                    TextInput::make('direccion')
                                        ->columnSpanFull(),

                                    Select::make('grado_instruccion')
                                        ->options(GradoInstruccion::class),

                                    Select::make('provincia')
                                        ->options(Provincia::class)
                                        ->default('Lima')
                                        ->required(),

                                    Select::make('distrito')
                                        ->options(DistritoLima::class),
                                ])
                                ->columns(2),

                            Step::make('Apoderado')
                                ->schema([
                                    Select::make('apoderado_tipo_documento')
                                        ->label('Tipo de documento del apoderado')
                                        ->options(TipoDocumento::class)
                                        ->required(),

                                    TextInput::make('apoderado_nro_documento')
                                        ->label('N° documento del apoderado')
                                        ->required()
                                        ->unique(Apoderado::class, 'nro_documento'),

                                    TextInput::make('apoderado_nombres')
                                        ->label('Nombres del apoderado')
                                        ->required()
                                        ->columnSpanFull(),

                                    TextInput::make('apoderado_apellido_paterno')
                                        ->label('Apellido paterno del apoderado')
                                        ->required(),

                                    TextInput::make('apoderado_apellido_materno')
                                        ->label('Apellido materno del apoderado')
                                        ->required(),

                                    TextInput::make('apoderado_telefono')
                                        ->label('Teléfono del apoderado')
                                        ->tel()
                                        ->nullable(),
                                ])
                                ->columns(2),
                        ]),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        // 1) Crear apoderado
                        $apoderado = Apoderado::create([
                            'tipo_documento'   => $data['apoderado_tipo_documento'] ?? null,
                            'nro_documento'    => $data['apoderado_nro_documento'] ?? null,
                            'nombres'          => $data['apoderado_nombres'] ?? null,
                            'apellido_paterno' => $data['apoderado_apellido_paterno'] ?? null,
                            'apellido_materno' => $data['apoderado_apellido_materno'] ?? null,
                            'telefono'         => $data['apoderado_telefono'] ?? null,
                        ]);

                        // 2) Datos del estudiante
                        $estudianteData = [
                            'tipo_documento'    => $data['tipo_documento'] ?? null,
                            'nro_documento'     => $data['nro_documento'] ?? null,
                            'nombres'           => $data['nombres'] ?? null,
                            'apellido_paterno'  => $data['apellido_paterno'] ?? null,
                            'apellido_materno'  => $data['apellido_materno'] ?? null,
                            'genero'            => $data['genero'] ?? null,
                            'estado_civil'      => $data['estado_civil'] ?? null,
                            'fecha_nacimiento'  => $data['fecha_nacimiento'] ?? null,
                            'telefono'          => $data['telefono'] ?? null,
                            'direccion'         => $data['direccion'] ?? null,
                            'email'             => $data['email'] ?? null,
                            'grado_instruccion' => $data['grado_instruccion'] ?? null,
                            'provincia'         => $data['provincia'] ?? null,
                            'distrito'          => $data['distrito'] ?? null,
                            'apoderado_id'      => $apoderado->id,
                        ];

                        // 3) Crear estudiante
                        $estudiante = Estudiante::create($estudianteData);

                        return $estudiante->getKey();
                    })
                    ->createOptionAction(
                        fn (Action $action) => $action
                            ->label('Nuevo estudiante')
                            ->modalHeading('Registrar estudiante y apoderado')
                            ->icon('heroicon-m-plus')
                    ),

                // ----------------------------------------
                // ESTADO DE MATRÍCULA
                // ----------------------------------------
                ToggleButtons::make('estado')
                    ->label('Estado')
                    ->options(EstadoMatricula::class)
                    ->default(EstadoMatricula::ENPROCESO)
                    ->inline()
                    ->disabled()
                    ->dehydrated(true),

                // ----------------------------------------
                // TIPO DE MATRÍCULA (ENUM REAL)
                // ----------------------------------------
                Select::make('tipo_matricula')
                    ->label('Tipo de matrícula')
                    ->options(TipoMatricula::class)   // opciones desde el enum
                    ->enum(TipoMatricula::class)      // 👈 el estado será TipoMatricula, no string
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (TipoMatricula|null $state, Set $set) {
                        // Al cambiar tipo, limpiamos sección, curso y textarea
                        $set('seccion_id', null);
                        $set('id_curso', null);
                        $set('cursos_matriculados', null);
                    }),

                // ----------------------------------------
                // SECCIÓN (FILTRADA POR TIPO DE MATRÍCULA)
                // ----------------------------------------
                Select::make('seccion_id')
                    ->label('Sección')
                    ->relationship(
                        name: 'seccion',
                        titleAttribute: 'id_seccion',
                        modifyQueryUsing: function (Builder $query, Get $get) {
                            /** @var TipoMatricula|null $tipoMatricula */
                            $tipoMatricula = $get('tipo_matricula');

                            if (! $tipoMatricula) {
                                $query->whereRaw('1 = 0');
                                return;
                            }

                            $query->whereHas('programa', function (Builder $q) use ($tipoMatricula) {
                                if ($tipoMatricula === TipoMatricula::PROG_ESTUDIO) {
                                    $q->where('tipo_programa', TipoPrograma::PROGRAMA_ESTUDIO->value);
                                } elseif ($tipoMatricula === TipoMatricula::FORM_CONTINUA) {
                                    $q->where('tipo_programa', TipoPrograma::FORMACION_CONTINUA->value);
                                } elseif ($tipoMatricula === TipoMatricula::CURSO_LIBRE) {
                                    $q->whereIn('tipo_programa', [
                                        TipoPrograma::PROGRAMA_ESTUDIO->value,
                                        TipoPrograma::FORMACION_CONTINUA->value,
                                    ]);
                                }
                            });
                        },
                    )
                    ->getOptionLabelFromRecordUsing(function (Seccion $seccion): string {
                        $programa  = $seccion->programa?->nombre_programa ?? 'Sin programa';

                        $turno     = $seccion->turno?->value ?? $seccion->turno;
                        $modalidad = $seccion->modalidad?->value ?? $seccion->modalidad;

                        $dias = is_array($seccion->dias)
                            ? implode(', ', $seccion->dias)
                            : $seccion->dias;

                        $horario = $seccion->horario ?? '';

                        return "{$programa} | Turno: {$turno} | Días: {$dias} | Horario: {$horario} | {$modalidad}";
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->disabled(fn (Get $get) => ! $get('tipo_matricula'))
                    ->afterStateHydrated(function ($state, Set $set) {
                        static::fillCursosDeSeccion($state, $set);
                    })
                    ->afterStateUpdated(function ($state, Set $set) {
                        static::fillCursosDeSeccion($state, $set);
                        $set('id_curso', null);
                    }),

                // ----------------------------------------
                // TEXTAREA INFORMATIVO DE CURSOS
                // ----------------------------------------
                Textarea::make('cursos_matriculados')
                    ->label('Cursos del programa de la sección')
                    ->rows(1)
                    ->autosize()
                    ->disabled()
                    ->dehydrated(false),

                // ----------------------------------------
                // CURSO (SOLO PARA CURSO LIBRE)
                // ----------------------------------------
                Select::make('id_curso')
                    ->label('Curso (solo para curso libre)')
                    ->options(function (Get $get) {
                        $seccionId = $get('seccion_id');

                        if (! $seccionId) {
                            return [];
                        }

                        $seccion = Seccion::with('programa.cursos')->find($seccionId);

                        if (! $seccion || ! $seccion->programa) {
                            return [];
                        }

                        return $seccion->programa
                            ->cursos()
                            ->orderBy('nombre_curso')
                            ->pluck('nombre_curso', 'id_curso') // key = id_curso, value = nombre_curso
                            ->toArray();
                    })
                    ->searchable()
                    ->live()
                    // oculto mientras NO sea curso libre
                    ->hidden(fn (Get $get) =>
                        $get('tipo_matricula') !== TipoMatricula::CURSO_LIBRE
                    )
                    // deshabilitado si no hay sección o no es curso libre
                    ->disabled(fn (Get $get) =>
                        ! $get('seccion_id')
                        || $get('tipo_matricula') !== TipoMatricula::CURSO_LIBRE
                    )
                    // requerido solo si es curso libre
                    ->required(fn (Get $get) =>
                        $get('tipo_matricula') === TipoMatricula::CURSO_LIBRE
                    ),
            ]);
    }

    /**
     * Llena el textarea con los cursos del programa de la sección seleccionada.
     */
    protected static function fillCursosDeSeccion($seccionId, Set $set): void
    {
        if (! $seccionId) {
            $set('cursos_matriculados', null);
            return;
        }

        $seccion = Seccion::find($seccionId);

        if (! $seccion) {
            $set('cursos_matriculados', 'Sección no encontrada.');
            return;
        }

        $cursos = Curso::query()
            ->where('id_programa', $seccion->id_programa)
            ->orderBy('nombre_curso')
            ->get();

        if ($cursos->isEmpty()) {
            $set('cursos_matriculados', 'Este programa no tiene cursos registrados.');
            return;
        }

        $texto = $cursos
            ->values()
            ->map(function ($curso, $index) {
                $n = $index + 1;
                return "{$n}. {$curso->nombre_curso}";
            })
            ->implode(PHP_EOL);

        $set('cursos_matriculados', $texto);
    }
}
