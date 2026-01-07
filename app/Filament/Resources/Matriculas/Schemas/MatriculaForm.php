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

use App\Services\OracleTusneService;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;

use Filament\Notifications\Notification;

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
                        // Limpiar estado de validación previo
                        $set('codigo_contribuyente_status', null);
                        
                        if (!$state) {
                            return;
                        }
                        
                        // Obtener el estudiante seleccionado
                        $estudiante = Estudiante::find($state);
                        
                        if (!$estudiante || !$estudiante->nro_documento) {
                            return;
                        }
                        
                        // Validar código de contribuyente
                        try {
                            $oracle = app(OracleTusneService::class);
                            $codigoReciente = $oracle->obtenerCodigoContribuyenteMasReciente($estudiante->nro_documento);
                            
                            if (!$codigoReciente || empty($codigoReciente->CODIGO)) {
                                // NO tiene código - mostrar modal y limpiar formulario
                                Notification::make()
                                    ->title('⚠️ Estudiante sin código de contribuyente')
                                    ->body("El estudiante {$estudiante->nombres} {$estudiante->apellido_paterno} {$estudiante->apellido_materno} no posee un código de contribuyente vigente. No es posible proseguir con la matrícula.")
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                
                                // Limpiar todos los campos del formulario
                                $set('estudiante_id', null);
                                $set('codigo_inscripcion', null);
                                $set('tipo_matricula', null);
                                $set('programa_intermediario', null);
                                $set('formacion_continua_intermediaria', null);
                                $set('horario_id', null);
                                $set('id_curso', null);
                                $set('cursos_matriculados', null);
                                $set('codigo_contribuyente_status', null);
                                
                                return;
                            }
                            
                            // SÍ tiene código - mostrar mensaje de éxito
                            $set('codigo_contribuyente_status', 'success');
                            
                            // Verificar si el estudiante tiene deudas pendientes
                            $matriculaService = app(\App\Services\MatriculaService::class);
                            $validacionDeudas = $matriculaService->estudianteTieneDeudas($state);
                            
                            if ($validacionDeudas['tiene_deuda']) {
                                Notification::make()
                                    ->title('⚠️ Estudiante con deudas pendientes')
                                    ->body($validacionDeudas['mensaje'])
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                
                                // Marcar estado de deuda
                                $set('estudiante_tiene_deuda', true);
                            } else {
                                $set('estudiante_tiene_deuda', false);
                            }
                            
                        } catch (\Exception $e) {
                            // Error en conexión Oracle
                            Notification::make()
                                ->title('Error de conexión')
                                ->body('No se pudo verificar el código de contribuyente. Por favor, intente nuevamente.')
                                ->warning()
                                ->send();
                            
                            $set('codigo_contribuyente_status', 'error');
                        }
                        
                        // Generar código de inscripción si todo está bien
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
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (Select $component) => $component
                                                ->getContainer()
                                                ->getComponent('modal_nro_documento')
                                                ?->state(null)
                                            ),
    
                                        TextInput::make('nro_documento')
                                            ->key('modal_nro_documento')
                                            ->required()
                                            ->unique(Estudiante::class, 'nro_documento')
                                            ->validationMessages([
                                                'unique' => 'Este número de documento ya está registrado para otro estudiante.',
                                            ])
                                            ->maxLength(function (Get $get) {
                                                $tipo = $get('tipo_documento');
                                                if (! $tipo instanceof TipoDocumento) {
                                                    $tipo = TipoDocumento::tryFrom($tipo);
                                                }
                                                return $tipo?->getMaxLength() ?? 8;
                                            })
                                            ->extraInputAttributes(function (Get $get) {
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
                                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),
    
                                        TextInput::make('apellido_paterno')
                                            ->required()
                                            ->regex('/^[\pL\s]+$/u')
                                            ->validationMessages([
                                                'regex' => 'Solo se permiten letras y espacios.',
                                            ])
                                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),
    
                                        TextInput::make('apellido_materno')
                                            ->required()
                                            ->regex('/^[\pL\s]+$/u')
                                            ->validationMessages([
                                                'regex' => 'Solo se permiten letras y espacios.',
                                            ])
                                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),
                                        
    
                                        Select::make('distrito')
                                            ->required()
                                            ->options(DistritoLima::class),
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
                                            ->tel()
                                            ->numeric()
                                            ->maxLength(9)
                                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"]),
    
                                        TextInput::make('email')
                                            ->label('Email')
                                            ->email(),
    
                                        TextInput::make('direccion'),
    
                                        Select::make('grado_instruccion')
                                            ->options(GradoInstruccion::class),
    
                                        Select::make('provincia')
                                            ->options(Provincia::class)
                                            ->default('Lima'),
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
                                        ->live()
                                        ->nullable(),

                                    TextInput::make('apoderado_nro_documento')
                                        ->label('N° documento del apoderado')
                                        ->unique(Apoderado::class, 'nro_documento')
                                        ->validationMessages([
                                            'unique' => 'Este número de documento ya está registrado para otro apoderado.',
                                        ])
                                        ->maxLength(function (Get $get) {
                                            $tipo = $get('apoderado_tipo_documento');
                                            if (! $tipo instanceof TipoDocumento) {
                                                $tipo = TipoDocumento::tryFrom($tipo);
                                            }
                                            return $tipo?->getMaxLength() ?? 8;
                                        })
                                        ->extraInputAttributes(function (Get $get) {
                                            $tipo = $get('apoderado_tipo_documento');
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

                                    TextInput::make('apoderado_nombres')
                                        ->label('Nombres del apoderado')
                                        ->columnSpanFull()
                                        ->regex('/^[\pL\s]*$/u')
                                        ->validationMessages([
                                            'regex' => 'Solo se permiten letras y espacios.',
                                        ])
                                        ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),

                                    TextInput::make('apoderado_apellido_paterno')
                                        ->label('Apellido paterno del apoderado')
                                        ->regex('/^[\pL\s]*$/u')
                                        ->validationMessages([
                                            'regex' => 'Solo se permiten letras y espacios.',
                                        ])
                                        ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),

                                    TextInput::make('apoderado_apellido_materno')
                                        ->label('Apellido materno del apoderado')
                                        ->regex('/^[\pL\s]*$/u')
                                        ->validationMessages([
                                            'regex' => 'Solo se permiten letras y espacios.',
                                        ])
                                        ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),

                                    TextInput::make('apoderado_telefono')
                                        ->label('Teléfono del apoderado')
                                        ->tel()
                                        ->numeric()
                                        ->maxLength(9)
                                        ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"])
                                        ->nullable(),
                                ])
                                ->columns(2),
                        ])->skippable(),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
                            $service = app(\App\Services\EstudianteService::class);
                            
                            // Separar datos de estudiante y apoderado
                            $apoderadoData = array_filter([
                                'tipo_documento' => $data['apoderado_tipo_documento'] ?? null,
                                'nro_documento' => $data['apoderado_nro_documento'] ?? null,
                                'nombres' => $data['apoderado_nombres'] ?? null,
                                'apellido_paterno' => $data['apoderado_apellido_paterno'] ?? null,
                                'apellido_materno' => $data['apoderado_apellido_materno'] ?? null,
                                'telefono' => $data['apoderado_telefono'] ?? null,
                            ]);
                            
                            $estudianteData = [
                                'tipo_documento' => $data['tipo_documento'],
                                'nro_documento' => $data['nro_documento'],
                                'nombres' => $data['nombres'],
                                'apellido_paterno' => $data['apellido_paterno'],
                                'apellido_materno' => $data['apellido_materno'],
                                'genero' => $data['genero'] ?? null,
                                'estado_civil' => $data['estado_civil'] ?? null,
                                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                                'telefono' => $data['telefono'] ?? null,
                                'direccion' => $data['direccion'] ?? null,
                                'email' => $data['email'] ?? null,
                                'grado_instruccion' => $data['grado_instruccion'] ?? null,
                                'provincia' => $data['provincia'] ?? null,
                                'distrito' => $data['distrito'] ?? null,
                            ];
                            
                            $estudiante = $service->crearConApoderado($estudianteData, $apoderadoData);
                            
                            return (int) $estudiante->id;
                        });
                    })
                    ->createOptionAction(
                        fn (Action $action) => $action
                            ->label('Nuevo estudiante')
                            ->modalHeading('Registrar estudiante y apoderado')
                            ->icon('heroicon-m-plus')
                    ),

                // ----------------------------------------
                // MENSAJE DE VALIDACIÓN DE CÓDIGO CONTRIBUYENTE
                // ----------------------------------------
                Placeholder::make('codigo_contribuyente_validacion')
                    ->label('')
                    ->content(function (Get $get): string {
                        $status = $get('codigo_contribuyente_status');
                        
                        if ($status === 'success') {
                            return '✓ Apto para matricular';
                        }
                        
                        return '';
                    })
                    ->visible(fn (Get $get) => $get('codigo_contribuyente_status') === 'success' && !$get('estudiante_tiene_deuda'))
                    ->extraAttributes([
                        'class' => 'text-success-600 font-semibold',
                        'style' => 'color: #10b981; font-weight: 600; margin-top: -0.5rem;'
                    ]),

                // ----------------------------------------
                // MENSAJE DE DEUDA PENDIENTE
                // ----------------------------------------
                Placeholder::make('deuda_pendiente_validacion')
                    ->label('')
                    ->content('⚠️ El estudiante tiene pagos vencidos. Debe regularizar sus deudas antes de matricularse.')
                    ->visible(fn (Get $get) => $get('estudiante_tiene_deuda') === true)
                    ->extraAttributes([
                        'class' => 'text-danger-600 font-semibold',
                        'style' => 'color: #ef4444; font-weight: 600; margin-top: -0.5rem;'
                    ]),

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

                            // Cargar relaciones necesarias
                            $query->with('programa');

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

                            // Solo mostrar horarios activos
                            $query->where('activo', true);
                        },
                    )
                    ->getOptionLabelFromRecordUsing(function (Horario $horario): string {
                        $programa  = $horario->programa?->nombre_programa ?? 'Sin programa';

                        $turno     = $horario->turno?->value ?? $horario->turno;
                        $modalidad = $horario->modalidad?->value ?? $horario->modalidad;

                        $dias = is_array($horario->dias)
                            ? implode(', ', $horario->dias)
                            : $horario->dias;

                        // Formatear hora_inicio y hora_fin usando Carbon para asegurar el formato correcto
                        $horarioTexto = '';
                        if (!empty($horario->hora_inicio) && !empty($horario->hora_fin)) {
                            try {
                                $inicio = \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i');
                                $fin = \Carbon\Carbon::parse($horario->hora_fin)->format('H:i');
                                $horarioTexto = "{$inicio} - {$fin}";
                            } catch (\Exception $e) {
                                // Si hay error al parsear, intentar mostrar directamente
                                $horarioTexto = substr($horario->hora_inicio ?? '', 0, 5) . ' - ' . substr($horario->hora_fin ?? '', 0, 5);
                            }
                        } elseif (!empty($horario->hora_inicio)) {
                            try {
                                $horarioTexto = \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i');
                            } catch (\Exception $e) {
                                $horarioTexto = substr($horario->hora_inicio ?? '', 0, 5);
                            }
                        }

                        return "{$programa} | Turno: {$turno} | Días: {$dias} | Hora: {$horarioTexto} | Modalidad: {$modalidad}";
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
                    ->rule(function (Get $get) {
                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                            $service = app(\App\Services\MatriculaService::class);
                            
                            // 1. Validar vacantes disponibles
                            $validacion = $service->validarVacantesDisponibles($value);
                            if (!$validacion['valido']) {
                                $fail($validacion['mensaje']);
                                return;
                            }
                            
                            // 2. Validar matrícula no duplicada (con tipo y curso)
                            $estudianteId = $get('estudiante_id');
                            $tipoMatricula = $get('tipo_matricula');
                            $cursoId = $get('id_curso');
                            
                            if ($estudianteId) {
                                $validacion = $service->validarDuplicado(
                                    $estudianteId, 
                                    $value,
                                    null, // matriculaIdIgnorar
                                    $tipoMatricula,
                                    $cursoId
                                );
                                if (!$validacion['valido']) {
                                    $fail($validacion['mensaje']);
                                }
                            }
                        };
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

                        $hoy = now()->startOfDay();

                        // Obtener cursos ordenados por fecha de inicio
                        return $horario->programa
                            ->cursos()
                            ->orderBy('fecha_inicio', 'asc')
                            ->get()
                            ->mapWithKeys(function ($curso) use ($hoy) {
                                $fechaInicio = $curso->fecha_inicio ? \Carbon\Carbon::parse($curso->fecha_inicio) : null;
                                $estado = '';
                                
                                if ($fechaInicio && $fechaInicio < $hoy) {
                                    $estado = ' [YA INICIÓ]';
                                }
                                
                                $fechaTexto = $fechaInicio ? $fechaInicio->format('d/m/Y') : 'Sin fecha';
                                
                                return [
                                    $curso->id_curso => $curso->nombre_curso . ' | Inicio: ' . $fechaTexto . $estado
                                ];
                            })
                            ->toArray();
                    })
                    ->disableOptionWhen(function (string $value, Get $get) {
                        $horarioId = $get('horario_id');
                        if (!$horarioId) return true;

                        $horario = Horario::with('programa.cursos')->find($horarioId);
                        if (!$horario || !$horario->programa) return true;

                        $hoy = now()->startOfDay();
                        
                        // Obtener el primer curso cuya fecha de inicio sea >= hoy
                        $primerCursoDisponible = $horario->programa
                            ->cursos()
                            ->whereNotNull('fecha_inicio')
                            ->whereDate('fecha_inicio', '>=', $hoy)
                            ->orderBy('fecha_inicio', 'asc')
                            ->first();
                        
                        // Si no hay cursos disponibles, deshabilitar todo
                        if (!$primerCursoDisponible) return true;
                        
                        // Solo habilitar el primer curso disponible
                        return (int) $value !== $primerCursoDisponible->id_curso;
                    })
                    ->helperText('Solo puede matricularse en el curso con fecha de inicio más próxima. Los cursos que ya iniciaron están deshabilitados.')
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

        $service = app(\App\Services\HorarioService::class);
        $tipoMatricula = $get('tipo_matricula') ?? TipoMatricula::PROGRAMA;
        
        $resultado = $service->obtenerCursosFormateados($horarioId, $tipoMatricula);
        
        $set('cursos_matriculados', $resultado['texto']);
    }

    /**
     * Genera el código de inscripción en formato "YYYY-XXX-NNN"
     * donde YYYY = año actual, XXX = ID del programa con 3 dígitos, 
     * y NNN = número secuencial de matrícula para ese programa/año
     */
    protected static function generarCodigoInscripcion(Set $set, Get $get): void
    {
        $horarioId = $get('horario_id');

        if (! $horarioId) {
            $set('codigo_inscripcion', null);
            return;
        }

        $service = app(\App\Services\MatriculaService::class);
        $codigo = $service->generarCodigoInscripcion($horarioId);

        $set('codigo_inscripcion', $codigo);
    }
}
