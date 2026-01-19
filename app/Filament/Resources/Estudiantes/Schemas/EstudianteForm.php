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
use App\Enums\GradoInstruccionEBR;
use App\Enums\CicloFormacion;
use App\Enums\Turno;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Models\Apoderado;

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
                                fn (Apoderado $record) => $record->nombre_completo
                            )
                            ->searchable(['nombres', 'apellido_paterno', 'apellido_materno'])
                            ->preload(),
                        Select::make('grado_instruccion')
                            ->options(GradoInstruccion::class),
                    ]) ->columnSpan('full'),
                
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
                            ->default('Ninguna')
                            ->live()
                            ->afterStateUpdated(fn (Select $component) => $component
                                ->getContainer()
                                ->getComponent('subtipo_discapacidad_component')
                                ?->state(null)
                            ),
                        Select::make('subtipo_discapacidad')
                            ->key('subtipo_discapacidad_component')
                            ->label('Subtipo de Discapacidad')
                            ->options(fn (Get $get) => SubtipoDiscapacidad::getOptionsPorTipo($get('tipo_discapacidad')))
                            ->visible(fn (Get $get) => in_array($get('tipo_discapacidad'), [
                                TipoDiscapacidad::AUDITIVA->value,
                                TipoDiscapacidad::VISUAL->value,
                                'Auditiva',
                                'Visual',
                            ])),
                        
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
                        Select::make('grado_instruccion_ebr')
                            ->label('Grado de Instrucción (EBR)')
                            ->options(GradoInstruccionEBR::class),
                        
                        // Tabla 208: Atributos de Matrícula
                        Select::make('ciclo_formacion')
                            ->label('Ciclo de Formación')
                            ->options(CicloFormacion::class),
                        Select::make('turno_matricula')
                            ->label('Turno de Matrícula')
                            ->options(Turno::class),
                    ])
                    ->columnSpan('full'),
            ]);
    }
}

