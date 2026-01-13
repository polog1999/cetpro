<?php

namespace App\Filament\Pages;

use App\Services\OracleTusneService;
use App\Services\LegacyRegistrationService;
use App\Models\Horario;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\HtmlString;
use Exception;
use UnitEnum;
use BackedEnum;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

// Enums
use App\Enums\TipoDocumento;
use App\Enums\TipoGenero;
use App\Enums\EstadoCivil;
use App\Enums\GradoInstruccion;
use App\Enums\Provincia;
use App\Enums\DistritoLima;

// Components
use Filament\Schemas\Schema; 
use Filament\Schemas\Components\Section; 
use Filament\Forms\Components\TextInput; 
use Filament\Forms\Components\Select; 
use Filament\Forms\Components\Placeholder; 
use Filament\Forms\Components\CheckboxList; 
use Filament\Actions\Action; 
use Filament\Forms\Components\Radio; 
use Filament\Schemas\Components\Wizard; // Wizard
use Filament\Forms\Components\DatePicker; // Fechas
use Filament\Schemas\Components\Grid;
use Carbon\Carbon;

class RegistrarAlumnosAntiguos extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';
    protected static string|null $navigationLabel = 'Registrar Alumnos Antiguos';
    protected static ?string $title = 'Registrar Alumnos Antiguos';
    protected static string|UnitEnum|null $navigationGroup = 'Administración';
    protected string $view = 'filament.pages.registrar-alumnos-antiguos';

    public ?array $data = [];
    public array $historialOracle = []; 
    public bool $busquedaRealizada = false;
    public ?string $codigoSeleccionado = null;

    public function mount(): void
    {
        $this->getForm('form')->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Wizard::make([
                    // PASO 1: BÚSQUEDA
                    Wizard\Step::make('Búsqueda')
                        ->description('Buscar alumno en Oracle')
                        ->schema([
                            Grid::make()
                                ->columns(12)
                                ->schema([
                                    TextInput::make('nro_documento')
                                        ->label('Número de Documento (DNI)')
                                        ->required()
                                        ->maxLength(15)
                                        ->live()
                                        ->columnSpan(9)
                                        ->extraInputAttributes(['wire:keydown.enter' => 'buscarEnOracle']),
                                    
                                    Placeholder::make('boton_buscar')
                                        ->hiddenLabel()
                                        ->columnSpan(3)
                                        ->content(new HtmlString('<button type="button" wire:click="buscarEnOracle" class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-primary bg-primary-600 text-white hover:bg-primary-500 focus-visible:ring-primary-500/50 shadow-sm gap-1.5 px-4 py-2 mt-6 w-full"><span class="fi-btn-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg></span> Buscar</button>')),
                                ]),
                            
                            Section::make('Resultados de la Búsqueda')
                                ->visible(fn () => $this->busquedaRealizada && !empty($this->historialOracle))
                                ->schema([
                                    Radio::make('codigo_seleccionado')
                                        ->label('Seleccione un registro para continuar:')
                                        ->options(function () {
                                            $options = [];
                                            foreach ($this->historialOracle as $codigo => $data) {
                                                $p = $data['datos_personales'];
                                                $options[$codigo] = "{$p['MCNAPENOMB']} (Código: {$codigo})";
                                            }
                                            return $options;
                                        })
                                        ->descriptions(function () {
                                            $descriptions = [];
                                            foreach ($this->historialOracle as $codigo => $data) {
                                                $p = $data['datos_personales'];
                                                $pagos = collect($data['pagos']);
                                                $totalImporte = $pagos->sum('IMPORTE');
                                                $cantPagos = $pagos->count();
                                                $pagosPendientes = $pagos->filter(fn($pago) => stripos($pago['ESTADO'], 'Pendiente') !== false)->count();
                                                $pagosCancelados = $cantPagos - $pagosPendientes;
                                                
                                                // Información personal
                                                $html = '<div class="text-sm space-y-4 mt-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">';
                                                
                                                // Encabezado de datos personales
                                                $html .= '<div style="padding-bottom: 12px; border-bottom: 1px solid #e5e7eb;">';
                                                $html .= '<h4 style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Datos Personales</h4>';
                                                $html .= '<div style="display: flex; flex-wrap: wrap; align-items: center; font-size: 14px; gap: 24px;">';
                                                $html .= '<div><span style="color: #6b7280;">DNI:</span>&nbsp;&nbsp;<span style="font-weight: 600; color: #1f2937;">' . e($p['MCNNRODI']) . '</span></div>';
                                                $html .= '<div style="color: #d1d5db;">|</div>';
                                                $html .= '<div><span style="color: #6b7280;">Sexo:</span>&nbsp;&nbsp;<span style="font-weight: 600; color: #1f2937;">' . e($p['SEXO'] === 'M' ? 'Masculino' : 'Femenino') . '</span></div>';
                                                $html .= '<div style="color: #d1d5db;">|</div>';
                                                $html .= '<div><span style="color: #6b7280;">Distrito:</span>&nbsp;&nbsp;<span style="font-weight: 600; color: #1f2937;">' . e($p['DISTRIDESC'] ?: 'No registrado') . '</span></div>';
                                                $html .= '</div>';
                                                $html .= '</div>';
                                                
                                                // Resumen de pagos
                                                $html .= '<div class="py-3 border-b border-gray-300 dark:border-gray-600">';
                                                $html .= '<h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Resumen de Pagos</h4>';
                                                $html .= '<div class="flex flex-wrap gap-3">';
                                                $html .= '<div class="px-4 py-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm"><span class="text-xs text-gray-500 dark:text-gray-400 block">Total</span><span class="text-lg font-bold text-gray-800 dark:text-gray-200">' . $cantPagos . '</span></div>';
                                                $html .= '<div class="px-4 py-2 bg-green-50 dark:bg-green-900/30 rounded-lg border border-green-200 dark:border-green-700"><span class="text-xs text-green-600 dark:text-green-400 block">Cancelados</span><span class="text-lg font-bold text-green-700 dark:text-green-300">' . $pagosCancelados . '</span></div>';
                                                $html .= '<div class="px-4 py-2 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg border border-yellow-200 dark:border-yellow-700"><span class="text-xs text-yellow-600 dark:text-yellow-400 block">Pendientes</span><span class="text-lg font-bold text-yellow-700 dark:text-yellow-300">' . $pagosPendientes . '</span></div>';
                                                $html .= '<div class="px-4 py-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-700"><span class="text-xs text-blue-600 dark:text-blue-400 block">Importe Total</span><span class="text-lg font-bold text-blue-700 dark:text-blue-300">S/ ' . number_format($totalImporte, 2) . '</span></div>';
                                                $html .= '</div>';
                                                $html .= '</div>';
                                                
                                                // Tabla de pagos
                                                $html .= '<div class="pt-2">';
                                                $html .= '<h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Detalle de Pagos</h4>';
                                                $html .= '<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">';
                                                $html .= '<table class="w-full text-sm">';
                                                $html .= '<thead class="bg-gray-100 dark:bg-gray-800">';
                                                $html .= '<tr>';
                                                $html .= '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Liquidación</th>';
                                                $html .= '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Concepto</th>';
                                                $html .= '<th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Importe</th>';
                                                $html .= '<th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Emitido</th>';
                                                $html .= '<th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Pagado</th>';
                                                $html .= '<th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Estado</th>';
                                                $html .= '</tr>';
                                                $html .= '</thead>';
                                                $html .= '<tbody class="divide-y divide-gray-200 dark:divide-gray-700">';
                                                
                                                foreach ($pagos as $pago) {
                                                    $esPendiente = stripos($pago['ESTADO'], 'Pendiente') !== false;
                                                    $rowClass = $esPendiente ? 'bg-yellow-50/50 dark:bg-yellow-900/10' : 'bg-white dark:bg-gray-900';
                                                    $estadoClass = $esPendiente 
                                                        ? 'text-yellow-800 dark:text-yellow-200 bg-yellow-100 dark:bg-yellow-800/50' 
                                                        : 'text-green-800 dark:text-green-200 bg-green-100 dark:bg-green-800/50';
                                                    
                                                    $html .= '<tr class="' . $rowClass . ' hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">';
                                                    $html .= '<td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">' . e($pago['LIQUIDACION']) . '</td>';
                                                    $html .= '<td class="px-4 py-3 text-gray-700 dark:text-gray-300">' . e($pago['CONCEPTO']) . '</td>';
                                                    $html .= '<td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-gray-200">S/ ' . number_format((float)$pago['IMPORTE'], 2) . '</td>';
                                                    $html .= '<td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">' . e($pago['EMITIDO']) . '</td>';
                                                    $html .= '<td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">' . e($pago['PAGADO'] ?: '-') . '</td>';
                                                    $html .= '<td class="px-4 py-3 text-center"><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ' . $estadoClass . '">' . e($pago['ESTADO']) . '</span></td>';
                                                    $html .= '</tr>';
                                                }
                                                
                                                $html .= '</tbody>';
                                                $html .= '</table>';
                                                $html .= '</div>';
                                                $html .= '</div>';
                                                $html .= '</div>';
                                                
                                                $descriptions[$codigo] = new HtmlString($html);
                                            }
                                            return $descriptions;
                                        })
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            $this->codigoSeleccionado = $state;
                                            $this->llenarDatosEstudiante($state);
                                        }),
                                ]),
                        ])
                        ->afterValidation(function () {
                            if (empty($this->codigoSeleccionado)) {
                                Notification::make()
                                    ->warning()
                                    ->title('Selección requerida')
                                    ->body('Debe buscar y seleccionar un registro antes de continuar.')
                                    ->send();
                                throw new Halt();
                            }
                        }),

                    // PASO 2: DATOS DEL ESTUDIANTE
                    Wizard\Step::make('Datos del Estudiante')
                        ->description('Verificar y completar información')
                        ->schema([
                            Section::make('Información Personal')
                                ->columns(2)
                                ->schema([
                                    Select::make('tipo_documento')
                                        ->label('Tipo Doc.')
                                        ->options(TipoDocumento::class)
                                        ->default(TipoDocumento::DNI)
                                        ->required(),
                                    TextInput::make('nro_documento')
                                        ->label('Nro Documento')
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(fn () => $this->data['nro_documento'] ?? null)
                                        ->formatStateUsing(fn ($state) => $this->data['nro_documento'] ?? $state),
                                    TextInput::make('codigo_contribuyente')
                                        ->label('Cód. Contribuyente')
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(fn () => $this->data['codigo_contribuyente'] ?? null)
                                        ->formatStateUsing(fn ($state) => $this->data['codigo_contribuyente'] ?? $state),

                                    TextInput::make('nombres')
                                        ->required()
                                        ->default(fn () => $this->data['nombres'] ?? null)
                                        ->formatStateUsing(fn ($state) => $this->data['nombres'] ?? $state),
                                    TextInput::make('apellido_paterno')
                                        ->required()
                                        ->default(fn () => $this->data['apellido_paterno'] ?? null)
                                        ->formatStateUsing(fn ($state) => $this->data['apellido_paterno'] ?? $state),
                                    TextInput::make('apellido_materno')
                                        ->required()
                                        ->default(fn () => $this->data['apellido_materno'] ?? null)
                                        ->formatStateUsing(fn ($state) => $this->data['apellido_materno'] ?? $state),
                                    
                                    Select::make('genero')
                                        ->label('Sexo')
                                        ->options(TipoGenero::class)
                                        ->required()
                                        ->default(fn () => $this->data['genero'] ?? null),
                                        
                                    DatePicker::make('fecha_nacimiento')
                                        ->label('Fecha Nacimiento')
                                        ->default(fn () => $this->data['fecha_nacimiento'] ?? null),
                                    
                                    Select::make('estado_civil')
                                        ->options(EstadoCivil::class),
                                    Select::make('grado_instruccion')
                                        ->options(GradoInstruccion::class),
                                ]),

                            Section::make('Contacto y Ubicación')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('direccion')
                                        ->columnSpanFull()
                                        ->default(fn () => $this->data['direccion'] ?? null)
                                        ->formatStateUsing(fn ($state) => $this->data['direccion'] ?? $state),
                                        
                                    Select::make('distrito')
                                        ->options(DistritoLima::class)
                                        ->searchable()
                                        ->default(fn () => $this->data['distrito'] ?? null),
                                        
                                    Select::make('provincia')
                                        ->options(Provincia::class)
                                        ->default(Provincia::LIMA),
                                    TextInput::make('telefono')
                                        ->default(fn () => $this->data['telefono'] ?? null)
                                        ->formatStateUsing(fn ($state) => $this->data['telefono'] ?? $state),
                                    TextInput::make('email')
                                        ->email()
                                        ->default(fn () => $this->data['email'] ?? null)
                                        ->formatStateUsing(fn ($state) => $this->data['email'] ?? $state),
                                ]),
                        ]),

                    // PASO 3: PAGOS Y MATRÍCULA
                    Wizard\Step::make('Matrícula y Pagos')
                        ->description('Seleccionar pagos a importar')
                        ->schema([
                            Select::make('horario_id')
                                ->label('Asignar a Horario (Local)')
                                ->options(Horario::with('programa')->get()->mapWithKeys(function ($h) {
                                    $turno = $h->turno instanceof \UnitEnum ? $h->turno->value : $h->turno;
                                    return [$h->id_horario => ($h->programa->nombre_programa ?? 'Sin Programa') . " - {$turno} ({$h->id_horario})"];
                                }))
                                ->required()
                                ->searchable(),

                            Section::make('Historial de Pagos en Oracle')
                                ->schema([
                                    CheckboxList::make('pagos_seleccionados')
                                        ->label('Seleccione los pagos que desea importar:')
                                        ->options(function () {
                                            if (!$this->codigoSeleccionado || !isset($this->historialOracle[$this->codigoSeleccionado])) {
                                                return [];
                                            }
                                            $pagos = $this->historialOracle[$this->codigoSeleccionado]['pagos'];
                                            $options = [];
                                            foreach ($pagos as $pago) {
                                                $val = $pago['LIQUIDACION'];
                                                $options[$val] = "{$pago['LIQUIDACION']} - S/ " . number_format((float)$pago['IMPORTE'], 2) . " ({$pago['ESTADO']})";
                                            }
                                            return $options;
                                        })
                                        ->descriptions(function () {
                                            if (!$this->codigoSeleccionado || !isset($this->historialOracle[$this->codigoSeleccionado])) {
                                                return [];
                                            }
                                            $pagos = $this->historialOracle[$this->codigoSeleccionado]['pagos'];
                                            $descriptions = [];
                                            foreach ($pagos as $pago) {
                                                $val = $pago['LIQUIDACION'];
                                                $pagado = $pago['PAGADO'] ?: 'Pendiente';
                                                $descriptions[$val] = "Concepto: {$pago['CONCEPTO']} | Emitido: {$pago['EMITIDO']} | Pagado: {$pagado}";
                                            }
                                            return $descriptions;
                                        })
                                        ->columns(1)
                                        ->bulkToggleable(),
                                ]),
                        ]),
                ])
                ->submitAction(new HtmlString('<button type="submit" class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-primary bg-primary-600 text-white hover:bg-primary-500 focus-visible:ring-primary-500/50 shadow-sm gap-1.5 px-3 py-2 w-full">Finalizar Registro</button>'))
            ])
            ->statePath('data');
    }

    public function buscarEnOracle(): void
    {
        $dni = $this->data['nro_documento'] ?? null;
        
        if (empty($dni)) {
            Notification::make()->warning()->title('Campo requerido')->body('Ingrese un número de documento para buscar.')->send();
            return;
        }

        try {
            $oracleService = app(OracleTusneService::class);
            $this->historialOracle = $oracleService->buscarHistorialPorDni($dni);

            if (empty($this->historialOracle)) {
                $this->busquedaRealizada = false;
                $this->codigoSeleccionado = null;
                Notification::make()->warning()->title('No encontrado')->body('No se encontró historial para este DNI.')->send();
                return;
            }

            $this->busquedaRealizada = true;
            Notification::make()->success()->title('Encontrado')->body('Se encontraron registros. Seleccione uno para continuar.')->send();

            // Auto-selección si solo hay 1
            if (count($this->historialOracle) === 1) {
                $codigo = array_key_first($this->historialOracle);
                $this->codigoSeleccionado = $codigo;
                $this->getForm('form')->fill([
                    'nro_documento' => $dni,
                    'codigo_seleccionado' => $codigo
                ]);
                $this->llenarDatosEstudiante($codigo);
            }

        } catch (Exception $e) {
            Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
        }
    }

    public function llenarDatosEstudiante(string $codigo): void
    {
        if (!isset($this->historialOracle[$codigo])) {
            return;
        }

        $datos = $this->historialOracle[$codigo]['datos_personales'];
        
        $sexoStr = $datos['SEXO'] === 'F' ? TipoGenero::FEMENINO : TipoGenero::MASCULINO;
        
        $distritoEnum = null;
        if (!empty($datos['DISTRIDESC'])) {
            $nombreDistrito = trim($datos['DISTRIDESC']);
            foreach (DistritoLima::cases() as $d) {
                if (mb_strtoupper($d->value) === mb_strtoupper($nombreDistrito)) {
                    $distritoEnum = $d; break;
                }
            }
        }
        
        $fechaNac = !empty($datos['MCNFECNAC']) ? Carbon::parse($datos['MCNFECNAC'])->format('Y-m-d') : null;

        // DEBUG: Mostrar notificación para confirmar que entra aquí
        Notification::make()
            ->info()
            ->title('Cargando datos...')
            ->body("Nombre: {$datos['MCNAPENOMB']}")
            ->send();

        $nuevosDatos = [
            'nro_documento' => $datos['MCNNRODI'],
            'codigo_contribuyente' => $datos['MCNCONTRIB'],
            'nombres' => $datos['MCNNOMBRE'],
            'apellido_paterno' => $datos['MCNAPEPAT'],
            'apellido_materno' => $datos['MCNAPEMAT'],
            'genero' => $sexoStr, // Enum
            'fecha_nacimiento' => $fechaNac,
            'direccion' => $datos['MCNDIRE'] ?? null, 
            'distrito' => $distritoEnum, // Enum o null
            'email' => $datos['MCNEMAIL'] ?? null,
            'telefono' => $datos['MCNROTELE'] ?? null,
        ];

        // ACTUALIZACIÓN CRÍTICA:
        // 1. Instanciamos el array $data actual (usando getRawState por si acaso, o simplemente mezclando con lo que ya tiene livewire)
        // livewire bind: public ?array $data = [];
        
        // Simplemente sobrescribimos las llaves específicas
        foreach ($nuevosDatos as $key => $value) {
            $this->data[$key] = $value;
        }
        
        // 2. Forzamos el re-fill del formulario
        $this->getForm('form')->fill($this->data);
    }

    public function registrar(): void
    {
        $formData = $this->getForm('form')->getState();
        $codigo = $this->codigoSeleccionado;
        
        if (!$codigo || !isset($this->historialOracle[$codigo])) {
            return;
        }

        try {
            $registrationService = app(LegacyRegistrationService::class);
            $datosGrupo = $this->historialOracle[$codigo];
            
            // Reconstruimos el array para el servicio con los datos EDITADOS en el wizard
            // OJO: El servicio usa $oracleData para crear el estudiante.
            // Deberíamos modificar el servicio para aceptar un objeto EstudianteData o 
            // pasarle los datos ya procesados del form.
            // POR AHORA: Actualizamos $oracleData con lo que el usuario editó en el form
            
            $oracleData = $datosGrupo['datos_personales'];
            $oracleData['MCNNOMBRE'] = $formData['nombres'];
            $oracleData['MCNAPEPAT'] = $formData['apellido_paterno'];
            $oracleData['MCNAPEMAT'] = $formData['apellido_materno'];
            // El servicio usa 'SEXO' => 'F'/'M', pero el form devuelve Enum.
            $oracleData['SEXO'] = ($formData['genero'] === TipoGenero::FEMENINO || $formData['genero'] === TipoGenero::FEMENINO->value) ? 'F' : 'M';
            $oracleData['MCNFECNAC'] = $formData['fecha_nacimiento']; // El servicio parsea esto? Si es Y-m-d capaz explote si espera dd/mm/yyyy. Revisar service.
            // Service: Carbon::parse($data['MCNFECNAC']). Carbon parsea Y-m-d bien.
            
            $oracleData['MCNDIRE'] = $formData['direccion'];
            $oracleData['MCNEMAIL'] = $formData['email'];
            $oracleData['MCNROTELE'] = $formData['telefono'];
            // Distrito y otros... el servicio mapea de nuevo. 
            // Mejor sería pasar los datos limpios.

            // FIX: El servicio espera 'MCNFECNAC' en cierto formato o null.
            // Pasaremos el listado de PAGOS dentro de oracleData
            $todosLosPagos = collect($datosGrupo['pagos']);
            $codigosLiquidacion = $formData['pagos_seleccionados'] ?? [];
            
            $pagosAImportar = $todosLosPagos->filter(function($p) use ($codigosLiquidacion) {
                return in_array($p['LIQUIDACION'], $codigosLiquidacion);
            });
            
            // Convertir a objetos para el servicio
            $oracleData['PAGOS'] = $pagosAImportar->map(fn($p) => (object)$p)->values()->all();

            $estudiante = $registrationService->registrarDesdeOracle(
                $oracleData,
                $formData['horario_id'],
                array_keys($oracleData['PAGOS']) 
            );

            // Actualizar campos extra que el servicio tal vez no mapeó bien porque espera estructura Oracle exacta
            // (Ej: grado_instruccion, estado_civil, provincia que no vienen de Oracle)
            $estudiante->update([
                'grado_instruccion' => $formData['grado_instruccion'],
                'estado_civil' => $formData['estado_civil'],
                'provincia' => $formData['provincia'],
                'distrito' => $formData['distrito'], // Forzar el distrito seleccionado
                'genero' => $formData['genero'],
                'direccion' => $formData['direccion'],
                'email' => $formData['email'],
                'telefono' => $formData['telefono'],
            ]);

            Notification::make()
                ->success()
                ->title('Registro Exitoso')
                ->body("Estudiante {$estudiante->nombres} registrado.")
                ->send();

            // Reset
            $this->redirect(static::getUrl());

        } catch (Exception $e) {
            Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // Solo usuarios con rol admin (es_admin = true) pueden acceder
        return $user->role?->es_admin === true;
    }
}