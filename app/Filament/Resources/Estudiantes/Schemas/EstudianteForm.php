<?php

namespace App\Filament\Resources\Estudiantes\Schemas;

use App\Enums\DistritoLima;
use App\Enums\EstadoCivil;
use App\Enums\GradoInstruccion;
use App\Enums\Provincia;
use App\Enums\TipoDocumento;
use App\Enums\TipoGenero;
// Enums del Censo
use App\Enums\TipoDiscapacidad;
use App\Enums\SubtipoDiscapacidad;
use App\Enums\TipoProgramaReparacion;
use App\Enums\LenguaMaterna;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Models\Apoderado;
use App\Models\Estudiante;
use App\Services\PideService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;

class EstudianteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->description('Información requerida')
                    ->columns(2)
                    ->components([
                        Select::make('tipo_documento')
                            ->default(TipoDocumento::DNI)
                            ->options(TipoDocumento::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(
                                function (Select $component, Set $set) {
                                    // Al cambiar el número, reseteamos los campos de identidad
                                    $set('numero_documento', null);
                                    $set('nombres', null);
                                    $set('apellido_paterno', null);
                                    $set('apellido_materno', null);
                                    return $component
                                        ->getContainer()
                                        ->getComponent('nro_documento_component')
                                        ->state(null);
                                }
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
                            ->live()
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
                            })
                            ->suffixActions(
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
                            ->readOnly(fn(Get $get) => $get('tipo_documento') == TipoDocumento::DNI && !$get('pide_fallo'))
                            ->extraInputAttributes([
                                'oninput' => "this.value = this.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')",
                                'style' => 'text-transform: uppercase'
                            ]),
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
                            ->readOnly(fn(Get $get) => $get('tipo_documento') == TipoDocumento::DNI && !$get('pide_fallo'))
                            ->validationMessages([
                                'regex' => 'Solo se permiten letras y espacios.',
                            ])
                            ->extraInputAttributes([
                                'oninput' => "this.value = this.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')",
                                'style' => 'text-transform: uppercase'
                            ]),
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
                            ->readOnly(fn(Get $get) => $get('tipo_documento') == TipoDocumento::DNI && !$get('pide_fallo'))
                            ->validationMessages([
                                'regex' => 'Solo se permiten letras y espacios.',
                            ])
                            ->extraInputAttributes([
                                'oninput' => "this.value = this.value.replace(/[^a-zA-Z\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')",
                                'style' => 'text-transform: uppercase'
                            ]),
                        Select::make('provincia')
                            ->options(Provincia::class)
                            ->default('Lima')
                            ->required(),
                        Select::make('distrito')
                            ->options(DistritoLima::class)
                            ->required(),
                    ])
                    ->columnSpan('full'),
                Section::make()
                    ->description('Información adicional opcional')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->components([
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
                                fn(Apoderado $record) => $record->nombre_completo
                            )
                            ->searchable(['nombres', 'apellido_paterno', 'apellido_materno'])
                            ->preload(),
                        Select::make('grado_instruccion')
                            ->options(GradoInstruccion::class),
                    ])->columnSpan('full'),

                // ==================== SECCIÓN DEL CENSO ====================
                Section::make('Información del Censo')
                    ->description('Datos requeridos para el censo escolar (Tablas 201-208)')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->components([
                        // Tabla 205: Discapacidad
                        Select::make('tipo_discapacidad')
                            ->label('Tipo de Discapacidad')
                            ->options(TipoDiscapacidad::class)
                            ->default(TipoDiscapacidad::NINGUNA->value)
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
                                if ($tipo instanceof TipoDiscapacidad) {
                                    $tipo = $tipo->value;
                                }
                                return SubtipoDiscapacidad::getOptionsPorTipo($tipo);
                            })
                            ->hidden(function (Get $get) {
                                $tipo = $get('tipo_discapacidad');
                                if (!$tipo) return true;
                                // Si ya es un enum, usarlo directamente
                                if ($tipo instanceof TipoDiscapacidad) {
                                    return !$tipo->tieneSubtipos();
                                }
                                // Si es string, convertir a enum
                                $tipoEnum = TipoDiscapacidad::tryFrom($tipo);
                                return !$tipoEnum || !$tipoEnum->tieneSubtipos();
                            }),

                        // Tabla 206: Situación de Vulnerabilidad
                        Select::make('tipo_programa_reparacion')
                            ->label('Programa de Reparación (Ley 28592)')
                            ->options(TipoProgramaReparacion::class)
                            ->default('Ninguno'),

                        // Tabla 207: Lengua Materna
                        Select::make('lengua_materna')
                            ->label('Lengua Materna')
                            ->options(LenguaMaterna::class),

                        // Tabla 203/204: Trayectoria Académica
                        TextInput::make('anio_egreso_ebr')
                            ->label('Año de Egreso EBR')
                            ->helperText('Año en que terminó el colegio')
                            ->numeric()
                            ->minValue(1950)
                            ->maxValue(now()->year)
                            ->maxLength(4)
                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4)"]),
                    ])
                    ->columnSpan('full'),
            ]);
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
                        $set('persona_id', $persona->id);
                        $set('nombres', $persona->nombres);
                        $set('apellido_paterno', $persona->apellido_paterno);
                        $set('apellido_materno', $persona->apellido_materno);
                        $set('foto_url', $persona->foto_url); // Traer foto de la BD
                        Notification::make()
                            ->title('Autocompletado')
                            ->body('Datos obtenidos correctamente')
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
