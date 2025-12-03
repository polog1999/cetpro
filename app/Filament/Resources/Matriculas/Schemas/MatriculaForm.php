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
use App\Models\Horario;
use App\Models\Curso;
use App\Models\Apoderado;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
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
                // INFORMACIÓN DE MATRÍCULA (PARTE SUPERIOR)
                // ----------------------------------------
                TextInput::make('codigo_inscripcion')
                    ->label('Código de inscripción')
                    ->prefix('📋')
                    ->placeholder('Se generará al seleccionar estudiante y horario')
                    ->disabled()
                    ->dehydrated(true)
                    ->columnSpan(1),

                TextInput::make('estado')
                    ->label('Estado Actual')
                    ->prefix('⊗')
                    ->default(EstadoMatricula::ENPROCESO->value)
                    ->disabled()
                    ->dehydrated(true)
                    ->columnSpan(1),

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
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        static::generarCodigoInscripcion($set, $get);
                    })
                    ->createOptionForm([
                        Wizard::make([
                            Step::make('Estudiante')
                                ->schema([
                                    Section::make('Información requerida')
                                    ->columns(2)
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
                                    ]),

                                    Section::make('Datos adicionales')
                                    ->columns(2)
                                    ->schema([
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
    
                                        TextInput::make('direccion'),
    
                                        Select::make('grado_instruccion')
                                            ->options(GradoInstruccion::class),
    
                                        Select::make('provincia')
                                            ->options(Provincia::class)
                                            ->default('Lima'),
    
                                        Select::make('distrito')
                                            ->options(DistritoLima::class),
                                    ])
                                    ->collapsed(),

                                ])
                                ->columns(1),

                            Step::make('Apoderado')
                                ->description('Opcional. Solo si requiere')
                                ->schema([
                                    Select::make('apoderado_tipo_documento')
                                        ->label('Tipo de documento del apoderado')
                                        ->options(TipoDocumento::class)
                                        ->nullable(),

                                    TextInput::make('apoderado_nro_documento')
                                        ->label('N° documento del apoderado')
                                        ->unique(Apoderado::class, 'nro_documento'),

                                    TextInput::make('apoderado_nombres')
                                        ->label('Nombres del apoderado')
                                        ->columnSpanFull(),

                                    TextInput::make('apoderado_apellido_paterno')
                                        ->label('Apellido paterno del apoderado'),

                                    TextInput::make('apoderado_apellido_materno')
                                        ->label('Apellido materno del apoderado'),

                                    TextInput::make('apoderado_telefono')
                                        ->label('Teléfono del apoderado')
                                        ->tel()
                                        ->nullable(),
                                ])
                                ->columns(2),
                        ])->skippable(),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        // 1) Crear apoderado
                        $apoderado = Apoderado::create([
                            'tipo_documento'   => $data['apoderado_tipo_documento'],
                            'nro_documento'    => $data['apoderado_nro_documento'],
                            'nombres'          => $data['apoderado_nombres'],
                            'apellido_paterno' => $data['apoderado_apellido_paterno'],
                            'apellido_materno' => $data['apoderado_apellido_materno'],
                            'telefono'         => $data['apoderado_telefono'],
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
                // TIPO DE MATRÍCULA (ENUM REAL)
                // ----------------------------------------
                Select::make('tipo_matricula')
                    ->label('Tipo de matrícula')
                    ->options(TipoMatricula::class)   // opciones desde el enum
                    ->enum(TipoMatricula::class)      // 👈 el estado será TipoMatricula, no string
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (TipoMatricula|null $state, Set $set) {
                        // Al cambiar tipo, limpiamos todos los campos relacionados
                        $set('programa_intermediario', null);
                        $set('formacion_continua_intermediaria', null);
                        $set('horario_id', null);
                        $set('id_curso', null);
                        $set('cursos_matriculados', null);
                    }),

                // ----------------------------------------
                // PROGRAMA INTERMEDIARIO (para Programa y Modulo)
                // No se almacena, solo para filtrar
                // ----------------------------------------
                Select::make('programa_intermediario')
                    ->label('Seleccionar Programa')
                    ->options(function () {
                        return \App\Models\Programa::where('tipo_programa', TipoPrograma::PROGRAMA_ESTUDIO)
                            ->orderBy('nombre_programa')
                            ->pluck('nombre_programa', 'id_programa')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->dehydrated(false) // No se guarda en BD
                    ->visible(fn (Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::PROGRAMA, TipoMatricula::MODULO])
                    )
                    ->required(fn (Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::PROGRAMA, TipoMatricula::MODULO])
                    )
                    ->afterStateUpdated(function ($state, Set $set) {
                        $set('horario_id', null);
                        $set('id_curso', null);
                        $set('cursos_matriculados', null);
                    }),

                // ----------------------------------------
                // FORMACION CONTINUA INTERMEDIARIA (para Formacion Continua y Curso)
                // No se almacena, solo para filtrar
                // ----------------------------------------
                Select::make('formacion_continua_intermediaria')
                    ->label('Seleccionar Formación Continua')
                    ->options(function () {
                        return \App\Models\Programa::where('tipo_programa', TipoPrograma::FORMACION_CONTINUA)
                            ->orderBy('nombre_programa')
                            ->pluck('nombre_programa', 'id_programa')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->dehydrated(false) // No se guarda en BD
                    ->visible(fn (Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::FORMACION_CONTINUA, TipoMatricula::CURSO])
                    )
                    ->required(fn (Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::FORMACION_CONTINUA, TipoMatricula::CURSO])
                    )
                    ->afterStateUpdated(function ($state, Set $set) {
                        $set('horario_id', null);
                        $set('id_curso', null);
                        $set('cursos_matriculados', null);
                    }),

                // ----------------------------------------
                // HORARIO (FILTRADO POR TIPO DE MATRÍCULA Y PROGRAMA)
                // ----------------------------------------
                Select::make('horario_id')
                    ->label('Horario')
                    ->relationship(
                        name: 'horario',
                        titleAttribute: 'id_horario',
                        modifyQueryUsing: function (Builder $query, Get $get) {
                            /** @var TipoMatricula|null $tipoMatricula */
                            $tipoMatricula = $get('tipo_matricula');

                            if (! $tipoMatricula) {
                                $query->whereRaw('1 = 0');
                                return;
                            }

                            // Filtrar por programa intermediario seleccionado
                            if ($tipoMatricula === TipoMatricula::PROGRAMA || $tipoMatricula === TipoMatricula::MODULO) {
                                $programaId = $get('programa_intermediario');
                                if ($programaId) {
                                    $query->where('id_programa', $programaId);
                                } else {
                                    $query->whereRaw('1 = 0');
                                }
                            }
                            // Filtrar por formacion continua intermediaria seleccionada
                            elseif ($tipoMatricula === TipoMatricula::FORMACION_CONTINUA || $tipoMatricula === TipoMatricula::CURSO) {
                                $formacionId = $get('formacion_continua_intermediaria');
                                if ($formacionId) {
                                    $query->where('id_programa', $formacionId);
                                } else {
                                    $query->whereRaw('1 = 0');
                                }
                            }
                        },
                    )
                    ->getOptionLabelFromRecordUsing(function (Horario $horario): string {
                        $programa  = $horario->programa?->nombre_programa ?? 'Sin programa';

                        $turno     = $horario->turno?->value ?? $horario->turno;
                        $modalidad = $horario->modalidad?->value ?? $horario->modalidad;

                        $dias = is_array($horario->dias)
                            ? implode(', ', $horario->dias)
                            : $horario->dias;

                        $horarioTexto = $horario->horario ?? '';

                        return "{$programa} | Turno: {$turno} | Días: {$dias} | Hora: {$horarioTexto} | {$modalidad}";
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->disabled(function (Get $get) {
                        $tipoMatricula = $get('tipo_matricula');
                        
                        if (! $tipoMatricula) {
                            return true;
                        }
                        
                        // Para Programa y Módulo, requiere programa_intermediario
                        if ($tipoMatricula === TipoMatricula::PROGRAMA || $tipoMatricula === TipoMatricula::MODULO) {
                            return ! $get('programa_intermediario');
                        }
                        
                        // Para Formación Continua y Curso, requiere formacion_continua_intermediaria
                        if ($tipoMatricula === TipoMatricula::FORMACION_CONTINUA || $tipoMatricula === TipoMatricula::CURSO) {
                            return ! $get('formacion_continua_intermediaria');
                        }
                        
                        return true;
                    })
                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                        static::fillCursosDeHorario($state, $set, $get);
                    })
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        static::fillCursosDeHorario($state, $set, $get);
                        $set('id_curso', null);
                        static::generarCodigoInscripcion($set, $get);
                    }),

                // ----------------------------------------
                // TEXTAREA INFORMATIVO DE CURSOS/MODULOS
                // ----------------------------------------
                Textarea::make('cursos_matriculados')
                    ->label(function (Get $get) {
                        $tipoMatricula = $get('tipo_matricula');
                        if ($tipoMatricula === TipoMatricula::PROGRAMA || $tipoMatricula === TipoMatricula::MODULO) {
                            return 'Módulos del programa del horario';
                        }
                        return 'Cursos de la formación continua del horario';
                    })
                    ->rows(1)
                    ->autosize()
                    ->disabled()
                    ->dehydrated(false),

                // ----------------------------------------
                // CURSO/MODULO (PARA CURSO Y MODULO)
                // ----------------------------------------
                Select::make('id_curso')
                    ->label(function (Get $get) {
                        $tipoMatricula = $get('tipo_matricula');
                        if ($tipoMatricula === TipoMatricula::MODULO) {
                            return 'Módulo';
                        }
                        return 'Curso';
                    })
                    ->options(function (Get $get) {
                        $horarioId = $get('horario_id');

                        if (! $horarioId) {
                            return [];
                        }

                        $horario = Horario::with('programa.cursos')->find($horarioId);

                        if (! $horario || ! $horario->programa) {
                            return [];
                        }

                        return $horario->programa
                            ->cursos()
                            ->orderBy('nombre_curso')
                            ->pluck('nombre_curso', 'id_curso') // key = id_curso, value = nombre_curso
                            ->toArray();
                    })
                    ->searchable()
                    ->live()
                    // visible solo para CURSO y MODULO
                    ->visible(fn (Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::CURSO, TipoMatricula::MODULO])
                    )
                    // deshabilitado si no hay horario
                    ->disabled(fn (Get $get) =>
                        ! $get('horario_id')
                    )
                    // requerido solo si es CURSO o MODULO
                    ->required(fn (Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::CURSO, TipoMatricula::MODULO])
                    ),
            ]);
    }

    /**
     * Llena el textarea con los cursos/modulos del programa del horario seleccionado.
     */
    protected static function fillCursosDeHorario($horarioId, Set $set, Get $get): void
    {
        if (! $horarioId) {
            $set('cursos_matriculados', null);
            return;
        }

        $horario = Horario::find($horarioId);

        if (! $horario) {
            $set('cursos_matriculados', 'Horario no encontrado.');
            return;
        }

        $cursos = Curso::query()
            ->where('id_programa', $horario->id_programa)
            ->orderBy('nombre_curso')
            ->get();

        if ($cursos->isEmpty()) {
            // Determinar el mensaje según el tipo de matrícula
            $tipoMatricula = $get('tipo_matricula');
            $mensaje = ($tipoMatricula === TipoMatricula::PROGRAMA || $tipoMatricula === TipoMatricula::MODULO)
                ? 'Este programa no tiene módulos registrados.'
                : 'Esta formación continua no tiene cursos registrados.';
            $set('cursos_matriculados', $mensaje);
            return;
        }

        $texto = $cursos
            ->values()
            ->map(function ($curso, $index) {
                $n = $index + 1;
                $nombre = $curso->nombre_curso;
                
                // Formatear las fechas si existen
                $fechaInicio = $curso->fecha_inicio 
                    ? \Carbon\Carbon::parse($curso->fecha_inicio)->format('d/m/Y')
                    : 'Sin fecha';
                    
                $fechaFin = $curso->fecha_termino 
                    ? \Carbon\Carbon::parse($curso->fecha_termino)->format('d/m/Y')
                    : 'Sin fecha';
                
                return "{$n}. {$nombre} | Inicio: {$fechaInicio} | Fin: {$fechaFin}";
            })
            ->implode(PHP_EOL);

        $set('cursos_matriculados', $texto);
    }

    /**
     * Genera el código de inscripción basado en el DNI del estudiante y el ID del horario.
     */
    protected static function generarCodigoInscripcion(Set $set, Get $get): void
    {
        $estudianteId = $get('estudiante_id');
        $horarioId = $get('horario_id');

        if (! $estudianteId || ! $horarioId) {
            $set('codigo_inscripcion', null);
            return;
        }

        // Obtener DNI del estudiante
        $estudiante = Estudiante::find($estudianteId);
        $dni = $estudiante?->nro_documento ?? 'SINDNI';

        // Generar código: {dni_alumno}{id_horario}
        $codigo = $dni . $horarioId;

        $set('codigo_inscripcion', $codigo);
    }
}
