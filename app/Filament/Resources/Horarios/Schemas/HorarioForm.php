<?php

namespace App\Filament\Resources\Horarios\Schemas;

use App\Enums\Modalidad;
use App\Enums\Turno;
use App\Enums\TipoPrograma; // Usando TipoPrograma estándar del sistema
use App\Models\Programa;
use App\Models\Docente;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Textarea;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

use App\Enums\TipoDocumento;
use Filament\Actions\Action;

class HorarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // TIPO DE PROGRAMA 
                ToggleButtons::make('tipo_programa')
                    ->label('Tipo de programa')
                    ->options([
                        TipoPrograma::PROGRAMA_ESTUDIO->value      => 'Programa',
                        TipoPrograma::FORMACION_CONTINUA->value    => 'Formación continua',
                    ])
                    ->inline()
                    ->required()
                    ->live()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Set $set, Get $get, $record) {
                        // Al editar, cargar el tipo_programa desde el programa relacionado
                        if ($record && $record->id_programa) {
                            $programa = Programa::find($record->id_programa);
                            if ($programa && $programa->tipo_programa) {
                                $set('tipo_programa', $programa->tipo_programa->value ?? $programa->tipo_programa);
                            }
                        }
                    })
                    ->afterStateUpdated(function (Set $set) {
                        // Resetea programa y textarea al cambiar tipo_programa
                        $set('id_programa', null);
                        $set('cursos_programa', null);
                    }),

                // PROGRAMA 
                Select::make('id_programa')
                    ->label('Programa')
                    ->options(function (Get $get) {
                        $tipo = $get('tipo_programa');
                        if (! $tipo) return [];
                        
                        return Programa::query()
                            ->where('tipo_programa', $tipo)
                            ->orderBy('nombre_programa')
                            ->pluck('nombre_programa', 'id_programa')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->disabled(fn (Get $get): bool => ! $get('tipo_programa'))
                    ->afterStateHydrated(function (Set $set, $state, $record) {
                        // Al editar, cargar los cursos del programa
                        if ($record && $state) {
                            $programa = Programa::with('cursos')->find($state);
                            
                            if (! $programa || $programa->cursos->isEmpty()) {
                                $set('cursos_programa', 'No hay cursos asignados a este programa.');
                                return;
                            }

                            $texto = $programa->cursos
                                ->map(function ($curso) {
                                    $nombre = $curso->nombre_curso
                                        ?? $curso->nombre
                                        ?? 'Sin nombre';
                                    return '- ' . $nombre;
                                })
                                ->implode(PHP_EOL);

                            $set('cursos_programa', $texto);
                        }
                    })
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $id = $get('id_programa');

                        if (! $id) {
                            $set('cursos_programa', null);
                            return;
                        }

                        $programa = Programa::with('cursos')->find($id);

                        if (! $programa || $programa->cursos->isEmpty()) {
                            $set('cursos_programa', 'No hay cursos asignados a este programa.');
                            return;
                        }

                        $texto = $programa->cursos
                            ->map(function ($curso) {
                                $nombre = $curso->nombre_curso
                                    ?? $curso->nombre
                                    ?? 'Sin nombre';

                                return '- ' . $nombre;
                            })
                            ->implode(PHP_EOL);

                        $set('cursos_programa', $texto);
                    }),

                // Textarea informativo de cursos
                Textarea::make('cursos_programa')
                    ->label('Cursos del programa seleccionado')
                    ->placeholder('Seleccione un programa para ver sus cursos.')
                    ->disabled()
                    ->dehydrated(false)
                    ->autosize()
                    ->rows(1)
                    ->columnSpanFull(),

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

                // DOCENTE
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
                    ->createOptionForm([
                        Select::make('tipo_documento')
                            ->label('Tipo de documento')
                            ->options(TipoDocumento::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Select $component) => $component
                                ->getContainer()
                                ->getComponent('docente_nro_documento')
                                ?->state(null)
                            ),

                        TextInput::make('nro_documento')
                            ->key('docente_nro_documento')
                            ->label('Nro. de documento')
                            ->required()
                            ->unique(Docente::class, 'nro_documento')
                            ->validationMessages([
                                'unique' => 'Este número de documento ya está registrado.',
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
                            ->label('Nombres')
                            ->required()
                            ->regex('/^[\pL\s]+$/u')
                            ->validationMessages([
                                'regex' => 'Solo se permiten letras y espacios.',
                            ])
                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),

                        TextInput::make('apellido_paterno')
                            ->label('Apellido paterno')
                            ->required()
                            ->regex('/^[\pL\s]+$/u')
                            ->validationMessages([
                                'regex' => 'Solo se permiten letras y espacios.',
                            ])
                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),

                        TextInput::make('apellido_materno')
                            ->label('Apellido materno')
                            ->required()
                            ->regex('/^[\pL\s]+$/u')
                            ->validationMessages([
                                'regex' => 'Solo se permiten letras y espacios.',
                            ])
                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^a-zA-Z\\sñÑáéíóúÁÉÍÓÚüÜ]/g, '')"]),
                    ])
                    ->createOptionAction(function (Action $action) {
                        return $action
                            ->label('Registrar docente')
                            ->modalHeading('Registrar nuevo docente')
                            ->modalSubmitActionLabel('Guardar docente');
                    })
                    ->createOptionUsing(function (array $data) {
                        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
                            // Crear el docente
                            $docente = Docente::create($data);
                            
                            // Buscar o crear el rol de Profesor
                            $rolProfesor = \App\Models\Role::firstOrCreate(
                                ['nombre' => 'Profesor'],
                                [
                                    'descripcion' => 'Docente con acceso al sistema para gestión de notas',
                                    'es_admin' => false,
                                ]
                            );
                            
                            // Crear el usuario automáticamente
                            \App\Models\Usuario::create([
                                'usuario' => $docente->nro_documento,
                                'password' => \Illuminate\Support\Facades\Hash::make($docente->nro_documento),
                                'docente_id' => $docente->id,
                                'role_id' => $rolProfesor->id,
                                'activo' => true,
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Docente y usuario creados')
                                ->body("Usuario: {$docente->nro_documento} / Contraseña: {$docente->nro_documento}")
                                ->persistent()
                                ->send();
                            
                            return $docente->getKey();
                        });
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
                    ->required()
                    ->rules([
                        function (Get $get, ?\App\Models\Horario $record) {
                            return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                $docenteId = $get('id_docente');
                                $aula = $get('aula');
                                $dias = $get('dias');
                                $horaInicio = $get('hora_inicio');
                                $horaFin = $value;

                                if (! $docenteId || ! $dias || ! $horaInicio || ! $horaFin) {
                                    return;
                                }

                                $query = \App\Models\Horario::query();

                                if ($record) {
                                    $query->where('id_horario', '!=', $record->id_horario);
                                }

                                $query->where(function ($q) use ($docenteId, $aula) {
                                    $q->where('id_docente', $docenteId);
                                    if ($aula) {
                                        $q->orWhere('aula', $aula);
                                    }
                                });

                                // Verificar superposición de días
                                $query->where(function ($q) use ($dias) {
                                    foreach ($dias as $dia) {
                                        // Asumiendo que 'dias' es JSON en la BD
                                        // SQLite/Postgres soportan whereJsonContains
                                        $q->orWhereJsonContains('dias', $dia);
                                    }
                                });

                                // Verificar superposición de horas
                                $query->where(function ($q) use ($horaInicio, $horaFin) {
                                    $q->where('hora_inicio', '<', $horaFin)
                                      ->where('hora_fin', '>', $horaInicio);
                                });

                                if ($query->exists()) {
                                    $fail('Existe un cruce de horarios para el docente o el aula seleccionada.');
                                }
                            };
                        },
                    ]),

                // AULA
                TextInput::make('aula')
                    ->label('Aula')
                    ->maxLength(255)
                    ->nullable(),

                // VACANTES
                TextInput::make('vacantes')
                    ->label('Vacantes')
                    ->numeric()
                    ->default(20)
                    ->required(),

                // ACTIVO
                \Filament\Forms\Components\Toggle::make('activo')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
            ]);
    }
}
