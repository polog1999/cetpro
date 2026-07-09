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
use App\Models\Unidad;
use App\Models\Apoderado;

use App\Services\OracleTusneService;
use App\Services\PideService;
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

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
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
                        fn(Estudiante $record): string =>
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

                        // 1. Primero verificar si tiene código guardado localmente (nuevo sistema)
                        if (!empty($estudiante->codigo_contribuyente)) {
                            // SÍ tiene código local - permitir matrícula
                            $set('codigo_contribuyente_status', 'success');
                            static::generarCodigoInscripcion($set, $get);
                            return;
                        }

                        // 2. Si no tiene código local, consultar Oracle (sistema antiguo)
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

                            // SÍ tiene código en Oracle - mostrar mensaje de éxito
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
                            // Error en conexión Oracle - permitir continuar si tiene código local
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
                    ->helperText(function (Get $get) {
                        $status = $get('codigo_contribuyente_status');
                        $tieneDeuda = $get('estudiante_tiene_deuda');

                        if ($tieneDeuda) {
                            return new \Illuminate\Support\HtmlString(
                                '<span style="color: #ef4444;">⚠️ Tiene pagos vencidos. Debe regularizar sus deudas.</span>'
                            );
                        }

                        if ($status === 'success') {
                            return new \Illuminate\Support\HtmlString(
                                '<span style="color: #10b981;">✓ Apto para matricular</span>'
                            );
                        }

                        return null;
                    })
                    ->createOptionForm([
                        Section::make('Información del Estudiante')
                            ->columns(2)
                            ->schema([
                                Select::make('tipo_documento')
                                    ->options(TipoDocumento::class)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(
                                        fn(Select $component) => $component
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
                                    })->suffixActions(
                                        [
                                            self::botonBuscarPersona()
                                        ]
                                    ),
                                Hidden::make('pide_fallo')->default(false)->live(),
                                TextInput::make('nombres')
                                    // 2. Reactividad: Transforma el valor real en el cliente (Alpine.js)
                                    ->extraAttributes([
                                        'x-on:input' => '$el.querySelector("input").value = $el.querySelector("input").value.toUpperCase()',
                                    ])
                                    // 3. Limpieza: Elimina espacios al perder el foco (opcional pero recomendado)
                                    ->trim()

                                    // 4. Seguridad: Asegura que llegue en mayúsculas al servidor
                                    ->dehydrateStateUsing(fn($state) => mb_strtoupper(trim($state)))
                                    ->required()
                                    ->regex('/^[\pL\s]+$/u')
                                    ->validationMessages([
                                        'regex' => 'Solo se permiten letras y espacios.',
                                    ])
                                    ->extraInputAttributes([
                                        'oninput' => "this.value = this.value.replace(/[^a-zA-Z\\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')",
                                        'style' => 'text-transform: uppercase'
                                    ])
                                    ->readOnly(fn(Get $get) => $get('tipo_documento') == TipoDocumento::DNI && !$get('pide_fallo')),

                                TextInput::make('apellido_paterno')
                                    // 2. Reactividad: Transforma el valor real en el cliente (Alpine.js)
                                    ->extraAttributes([
                                        'x-on:input' => '$el.querySelector("input").value = $el.querySelector("input").value.toUpperCase()',
                                    ])
                                    // 3. Limpieza: Elimina espacios al perder el foco (opcional pero recomendado)
                                    ->trim()

                                    // 4. Seguridad: Asegura que llegue en mayúsculas al servidor
                                    ->dehydrateStateUsing(fn($state) => mb_strtoupper(trim($state)))
                                    ->required()
                                    ->regex('/^[\pL\s]+$/u')
                                    ->validationMessages([
                                        'regex' => 'Solo se permiten letras y espacios.',
                                    ])
                                    ->extraInputAttributes([
                                        'oninput' => "this.value = this.value.replace(/[^a-zA-Z\\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')",
                                        'style' => 'text-transform: uppercase'
                                    ])
                                    ->readOnly(fn(Get $get) => $get('tipo_documento') == TipoDocumento::DNI && !$get('pide_fallo')),

                                TextInput::make('apellido_materno')
                                    // 2. Reactividad: Transforma el valor real en el cliente (Alpine.js)
                                    ->extraAttributes([
                                        'x-on:input' => '$el.querySelector("input").value = $el.querySelector("input").value.toUpperCase()',
                                    ])
                                    // 3. Limpieza: Elimina espacios al perder el foco (opcional pero recomendado)
                                    ->trim()

                                    // 4. Seguridad: Asegura que llegue en mayúsculas al servidor
                                    ->dehydrateStateUsing(fn($state) => mb_strtoupper(trim($state)))
                                    ->required()
                                    ->regex('/^[\pL\s]+$/u')
                                    ->validationMessages([
                                        'regex' => 'Solo se permiten letras y espacios.',
                                    ])
                                    ->extraInputAttributes([
                                        'oninput' => "this.value = this.value.replace(/[^a-zA-Z\\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')",
                                        'style' => 'text-transform: uppercase'
                                    ])
                                    ->readOnly(fn(Get $get) => $get('tipo_documento') == TipoDocumento::DNI && !$get('pide_fallo')),


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

                        Section::make('Información del Censo')
                            ->description('Datos requeridos para el censo escolar')
                            ->columns(2)
                            ->schema([
                                Select::make('tipo_discapacidad')
                                    ->label('Tipo de Discapacidad')
                                    ->options(\App\Enums\TipoDiscapacidad::class)
                                    ->default(\App\Enums\TipoDiscapacidad::NINGUNA->value)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('subtipo_discapacidad', null);
                                    }),
                                Select::make('subtipo_discapacidad')
                                    ->label('Subtipo de Discapacidad')
                                    ->options(function (Get $get) {
                                        $tipo = $get('tipo_discapacidad');
                                        if (!$tipo) return [];
                                        // Si ya es un enum, obtener el value
                                        if ($tipo instanceof \App\Enums\TipoDiscapacidad) {
                                            $tipo = $tipo->value;
                                        }
                                        return \App\Enums\SubtipoDiscapacidad::getOptionsPorTipo($tipo);
                                    })
                                    ->hidden(function (Get $get) {
                                        $tipo = $get('tipo_discapacidad');
                                        if (!$tipo) return true;
                                        // Si ya es un enum, usarlo directamente
                                        if ($tipo instanceof \App\Enums\TipoDiscapacidad) {
                                            return !$tipo->tieneSubtipos();
                                        }
                                        // Si es string, convertir a enum
                                        $tipoEnum = \App\Enums\TipoDiscapacidad::tryFrom($tipo);
                                        return !$tipoEnum || !$tipoEnum->tieneSubtipos();
                                    }),

                                Select::make('tipo_programa_reparacion')
                                    ->label('Programa de Reparación')
                                    ->options(\App\Enums\TipoProgramaReparacion::class)
                                    ->default('Ninguno'),

                                Select::make('lengua_materna')
                                    ->label('Lengua Materna')
                                    ->options(\App\Enums\LenguaMaterna::class),

                                TextInput::make('anio_egreso_ebr')
                                    ->label('Año de Egreso EBR')
                                    ->numeric()
                                    ->maxLength(4)
                                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4)"]),
                            ])
                            ->collapsed(),

                        Section::make('Apoderado (Opcional)')
                            ->description('Solo si el estudiante requiere apoderado')
                            ->columns(2)
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
                            ->collapsed(),
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
                                // Campos del Censo Escolar
                                'tipo_discapacidad' => $data['tipo_discapacidad'] ?? null,
                                'subtipo_discapacidad' => $data['subtipo_discapacidad'] ?? null,
                                'tipo_programa_reparacion' => $data['tipo_programa_reparacion'] ?? null,
                                'lengua_materna' => $data['lengua_materna'] ?? null,
                                'anio_egreso_ebr' => $data['anio_egreso_ebr'] ?? null,
                            ];

                            $estudiante = $service->crearConApoderado($estudianteData, $apoderadoData);

                            // ======================================================
                            // CREAR CÓDIGO DE CONTRIBUYENTE EN ORACLE
                            // ======================================================
                            try {
                                $oracle = app(OracleTusneService::class);
                                $codigo = $oracle->crearContribuyente($estudiante);

                                if ($codigo) {
                                    // Guardar el código en el estudiante
                                    $estudiante->codigo_contribuyente = $codigo;
                                    $estudiante->save();

                                    Notification::make()
                                        ->success()
                                        ->title('Estudiante y contribuyente creados')
                                        ->body("Código de contribuyente: {$codigo}")
                                        ->send();

                                    \Log::info('Contribuyente creado desde modal de matrícula', [
                                        'estudiante_id' => $estudiante->id,
                                        'codigo' => $codigo,
                                    ]);
                                } else {
                                    Notification::make()
                                        ->warning()
                                        ->title('Estudiante creado')
                                        ->body('No se pudo generar el código de contribuyente.')
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                \Log::error('Error al crear contribuyente desde modal de matrícula', [
                                    'estudiante_id' => $estudiante->id,
                                    'error' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->warning()
                                    ->title('Estudiante creado sin código de contribuyente')
                                    ->body('Error: ' . $e->getMessage())
                                    ->send();
                            }

                            return (int) $estudiante->id;
                        });
                    })
                    ->createOptionAction(
                        fn(Action $action) => $action
                            ->label('Nuevo estudiante')
                            ->modalHeading('Registrar estudiante y apoderado')
                            ->icon('heroicon-m-plus')
                    )
                    ->disabled(fn($context) => $context === 'edit') // 👈 Bloqueado al editar
                    ->dehydrated(true), // Asegura que se envíe el valor original al guardar,

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
                    })
                    ->disabled(fn($context) => $context === 'edit') // 👈 Bloqueado al editar
                    ->dehydrated(true),
                TextInput::make('num_cuotas_personalizado')
                    ->label('Meses a estudiar / Nro de Cuotas')
                    ->placeholder('Dejar en blanco para calcular todo el programa')
                    ->helperText('Indique cuántos meses pagará el alumno si solo estudiará un periodo parcial (ej: de Marzo a Julio = 5 cuotas).')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(12)
                    ->live()
                    ->visible(
                        fn(Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::PROGRAMA, TipoMatricula::FORMACION_CONTINUA])
                    ),
                Toggle::make('cobrar_mes_actual')
                    ->label('¿Cobrar mes actual?')
                    ->helperText('Marque esta opción si el estudiante iniciará sus clases este mes y debe pagar la cuota del mes en curso.')
                    ->default(true) // Por defecto sugerimos cobrarlo
                    ->live()
                    ->visible(
                        fn(Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::PROGRAMA, TipoMatricula::FORMACION_CONTINUA])
                    ),
                // ----------------------------------------
                // PROGRAMA INTERMEDIARIO (para Programa y Modulo)
                // No se almacena, solo para filtrar
                // Solo muestra programas completos (donde suma de cursos >= duración)
                // ----------------------------------------
                Select::make('programa_intermediario')
                    ->label('Seleccionar Programa')
                    ->options(function (Get $get) {
                        $tipoMatricula = $get('tipo_matricula');
                        $query = \App\Models\Programa::where('tipo_programa', TipoPrograma::PROGRAMA_ESTUDIO);

                        // Solo filtrar completos si es matrícula por PROGRAMA completo.
                        // Para MODULO o UNIDAD, permitimos programas "incompletos" siempre que tengan cursos.
                        if ($tipoMatricula === TipoMatricula::PROGRAMA) {
                            $query->completos();
                        } else {
                            // Para MODULO/UNIDAD, al menos debe tener algún curso/módulo definido
                            $query->has('cursos');
                        }

                        return $query
                            ->orderBy('nombre_programa')
                            ->pluck('nombre_programa', 'id_programa')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->dehydrated(false) // No se guarda en BD
                    ->visible(
                        fn(Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::PROGRAMA, TipoMatricula::MODULO, TipoMatricula::UNIDAD])
                    )
                    ->required(
                        fn(Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::PROGRAMA, TipoMatricula::MODULO, TipoMatricula::UNIDAD])
                    )
                    ->afterStateUpdated(function ($state, Set $set) {
                        $set('horario_id', null);
                        $set('id_curso', null);
                        $set('cursos_matriculados', null);
                    })
                    // Validación: no permitir MODULO si el programa tiene solo un módulo
                    ->rule(function (Get $get) {
                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                            $tipoMatricula = $get('tipo_matricula');

                            // Solo validar si es matrícula por MODULO
                            if ($tipoMatricula !== TipoMatricula::MODULO) {
                                return;
                            }

                            if (!$value) {
                                return;
                            }

                            // Contar módulos del programa
                            $programa = \App\Models\Programa::with('cursos')->find($value);
                            $cantidadModulos = $programa?->cursos?->count() ?? 0;

                            if ($cantidadModulos <= 1) {
                                $fail('Este programa tiene solo un módulo. Debe matricularse en el programa completo, no por módulo individual.');
                            }
                        };
                    })
                    ->disabled(fn($context) => $context === 'edit') // 👈 Bloqueado al editar
                    ->dehydrated(false),

                // ----------------------------------------
                // FORMACION CONTINUA INTERMEDIARIA (para Formacion Continua y Curso)
                // No se almacena, solo para filtrar
                // Solo muestra formaciones continuas completas (donde suma de cursos >= duración)
                // ----------------------------------------
                Select::make('formacion_continua_intermediaria')
                    ->label('Seleccionar Formación Continua')
                    ->options(function () {
                        return \App\Models\Programa::where('tipo_programa', TipoPrograma::FORMACION_CONTINUA)
                            ->completos() // Solo programas completos
                            ->orderBy('nombre_programa')
                            ->pluck('nombre_programa', 'id_programa')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->dehydrated(false) // No se guarda en BD
                    ->visible(
                        fn(Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::FORMACION_CONTINUA, TipoMatricula::CURSO])
                    )
                    ->required(
                        fn(Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::FORMACION_CONTINUA, TipoMatricula::CURSO])
                    )
                    ->afterStateUpdated(function ($state, Set $set) {
                        $set('horario_id', null);
                        $set('id_curso', null);
                        $set('cursos_matriculados', null);
                    })
                    // Validación: no permitir CURSO si la formación tiene solo un curso
                    ->rule(function (Get $get) {
                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                            $tipoMatricula = $get('tipo_matricula');

                            // Solo validar si es matrícula por CURSO
                            if ($tipoMatricula !== TipoMatricula::CURSO) {
                                return;
                            }

                            if (!$value) {
                                return;
                            }

                            // Contar cursos de la formación continua
                            $programa = \App\Models\Programa::with('cursos')->find($value);
                            $cantidadCursos = $programa?->cursos?->count() ?? 0;

                            if ($cantidadCursos <= 1) {
                                $fail('Esta formación continua tiene solo un curso. Debe matricularse en la formación continua completa, no por curso individual.');
                            }
                        };
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

                            // Cargar relaciones necesarias (programa y docente)
                            $query->with(['programa', 'docente']);

                            // Filtrar por programa intermediario seleccionado
                            if (in_array($tipoMatricula, [TipoMatricula::PROGRAMA, TipoMatricula::MODULO, TipoMatricula::UNIDAD])) {
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
                        $docente   = $horario->docente?->nombre_completo ?? 'Sin docente';

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

                        return "{$programa} | {$docente} | {$turno} | {$dias} | {$horarioTexto} | {$modalidad}";
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->disabled(function (Get $get) {
                        $tipoMatricula = $get('tipo_matricula');

                        if (! $tipoMatricula) {
                            return true;
                        }

                        // Para Programa y Módulo y Unidad, requiere programa_intermediario
                        if (in_array($tipoMatricula, [TipoMatricula::PROGRAMA, TipoMatricula::MODULO, TipoMatricula::UNIDAD])) {
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

                            // Vacantes: el aforo es solo un formalismo, no bloquea matrículas

                            // Validar matrícula no duplicada (con tipo, curso y unidad)
                            $estudianteId = $get('estudiante_id');
                            $tipoMatricula = $get('tipo_matricula');
                            $cursoId = $get('id_curso');
                            $unidadId = $get('id_unidad');

                            if ($estudianteId) {
                                $validacion = $service->validarDuplicado(
                                    $estudianteId,
                                    $value,
                                    null, // matriculaIdIgnorar
                                    $tipoMatricula,
                                    $cursoId,
                                    $unidadId
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
                    })
                    ->disabled(fn($context) => $context === 'edit') // 👈 Bloqueado al editar
                    ->dehydrated(true),

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



                        // Obtener cursos ordenados por fecha de inicio
                        return $horario->programa
                            ->cursos()
                            ->orderBy('fecha_inicio', 'asc')
                            ->get()
                            ->mapWithKeys(function ($curso) {
                                $fechaInicio = $curso->fecha_inicio ? \Carbon\Carbon::parse($curso->fecha_inicio) : null;
                                $fechaTexto = $fechaInicio ? $fechaInicio->format('d/m/Y') : 'Sin fecha';

                                return [
                                    $curso->id_curso => $curso->nombre_curso . ' | Inicio: ' . $fechaTexto
                                ];
                            })
                            ->toArray();
                    })
                    ->helperText('Seleccione el curso o módulo en el que desea matricularse.')
                    ->searchable()
                    ->live()
                    // visible solo para CURSO y MODULO y UNIDAD
                    ->visible(
                        fn(Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::CURSO, TipoMatricula::MODULO, TipoMatricula::UNIDAD])
                    )
                    // deshabilitado si no hay horario
                    ->disabled(
                        fn(Get $get) =>
                        ! $get('horario_id')
                    )
                    // requerido solo si es CURSO o MODULO o UNIDAD
                    ->required(
                        fn(Get $get) =>
                        in_array($get('tipo_matricula'), [TipoMatricula::CURSO, TipoMatricula::MODULO, TipoMatricula::UNIDAD])
                    )
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $set('id_unidad', null);
                        static::generarCodigoInscripcion($set, $get);
                    })
                    // ... tu lógica ...
                    ->disabled(fn($context) => $context === 'edit') // 👈 Bloqueado al editar
                    ->dehydrated(true),

                // ----------------------------------------
                // UNIDAD (PARA UNIDAD)
                // ----------------------------------------
                Select::make('id_unidad')
                    ->label('Unidad')
                    ->options(function (Get $get) {
                        $cursoId = $get('id_curso');
                        if (!$cursoId) {
                            return [];
                        }

                        return Unidad::where('id_curso', $cursoId)
                            ->ordenado()
                            ->pluck('nombre_unidad', 'id_unidad');
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    // visible solo para UNIDAD
                    ->visible(
                        fn(Get $get) =>
                        $get('tipo_matricula') === TipoMatricula::UNIDAD
                    )
                    // deshabilitado si no hay curso
                    ->disabled(
                        fn(Get $get) =>
                        ! $get('id_curso')
                    )
                    // requerido solo si es UNIDAD
                    ->required(
                        fn(Get $get) =>
                        $get('tipo_matricula') === TipoMatricula::UNIDAD
                    )
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        static::generarCodigoInscripcion($set, $get);
                    })
                    ->disabled(fn($context) => $context === 'edit') // 👈 Bloqueado al editar
                    ->dehydrated(true),
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
     * Genera el código de inscripción basado en tipo de matrícula:
     * - PROGRAMA/FORMACION_CONTINUA: AñoDNIHorarioID
     * - CURSO/MODULO: AñoDNIHorarioIDCursoID
     * - UNIDAD: AñoDNIHorarioIDCursoIDUnidadID
     */
    protected static function generarCodigoInscripcion(Set $set, Get $get): void
    {
        $horarioId = $get('horario_id');
        $estudianteId = $get('estudiante_id');
        $cursoId = $get('id_curso');
        $unidadId = $get('id_unidad');

        if (! $horarioId) {
            $set('codigo_inscripcion', null);
            return;
        }

        $service = app(\App\Services\MatriculaService::class);
        $codigo = $service->generarCodigoInscripcion($horarioId, $estudianteId, $cursoId, $unidadId);

        $set('codigo_inscripcion', $codigo);
    }
    protected static function botonBuscarPersona()
    {
        return Action::make('buscar_persona')
            ->color('success')
            ->icon('heroicon-m-magnifying-glass')
            ->visible(fn(Get $get) => $get('tipo_documento') == TipoDocumento::DNI)
            ->extraAttributes([
                // Forzamos al texto/ícono a ser verde y usamos !important (text-success-600)
                'class' => '[&_.fi-icon]:!text-success-600 dark:[&_.fi-icon]:!text-success-400',
            ])
            ->action(function ($state, Set $set, Get $get) {
                if (!$state) return;

                // 2. Buscar en tabla PERSONAS (Si ya fue visitante antes)
                if (strlen($state) === 8) {

                    $persona = Estudiante::where('nro_documento', $state)->first();

                      if ($persona) {
                    
                        $set('nombres', null);
                        $set('apellido_paterno', null);
                        $set('apellido_materno', null);
                      
                        Notification::make()
                            ->title('Ya se encuentra registrado')
                            ->success()
                            ->send();
                        return;
                    }


                    // 3. Si no existe en BD, Consultar al PIDE
                    // Supongamos que tienes un Service: PideService::consultar($dni)
                    $datosPide = PideService::ws_reniec($state);

                    if ($datosPide['codResu'] === '0000') {
                        $set('pide_fallo', false); // Activamos edición manual
                        $set('nombres', $datosPide['nombre']);
                        $set('apellido_paterno', $datosPide['paterno']);
                        $set('apellido_materno', $datosPide['materno']);
                        $set('foto_url', '/uploads/foto_dni/' . $state . '.png');
                        Notification::make()
                            ->title('Datos del PIDE')
                            ->body('Se consumió el PIDE')
                            ->success()
                            ->send();
                    } else {
                        $datosApiPeru = PideService::apiPeruDni($state);

                        if ($datosApiPeru['success']) {
                            // dd('probando');
                            $set('pide_fallo', false); // Activamos edición manual
                            $set('nombres', $datosApiPeru['data']['nombres']);
                            $set('apellido_paterno', $datosApiPeru['data']['apellido_paterno']);
                            $set('apellido_materno', $datosApiPeru['data']['apellido_materno']);
                            Notification::make()
                                ->title('Datos del ApisPeru')
                                ->body('Se consumió el ApisPeru')
                                ->success()
                                ->send();
                        } else {
                            $datosApisNet = PideService::apisNet($state);

                            if ($datosApisNet['success']) {
                                $set('pide_fallo', false); // Activamos edición manual
                                $set('nombres', $datosApisNet['nombres']);
                                $set('apellido_paterno', $datosApisNet['apellidoPaterno']);
                                $set('apellido_materno', $datosApisNet['apellidoMaterno']);
                                Notification::make()
                                    ->title('Datos de ApisNet')
                                    ->body('Se consumió el ApisNet')
                                    ->success()
                                    ->send();
                            } else {
                                // FALLÓ EL PIDE
                                $set('pide_fallo', true); // Activamos edición manual
                                $set('nombres', null);
                                $set('apellido_paterno', null);
                                $set('apellido_materno', null);
                                $set('foto_url', null);
                                Notification::make()
                                    ->title('PIDE no disponible')
                                    ->body('Complete los datos manualmente.')
                                    ->warning()
                                    ->send();
                            }
                        }
                    }
                } else {
                    Notification::make()
                        ->title('Alerta')
                        ->body('El DNI debe tener 8 dígitos')
                        ->warning()
                        ->send();
                }
            });
    }
}
