<?php

namespace App\Filament\Resources\Matriculas\Pages;

use App\Filament\Resources\Matriculas\MatriculaResource;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Horario;
use App\Enums\TipoMatricula;
use App\Enums\EstadoMatricula;
use App\Enums\Tip;
use Filament\Resources\Pages\Page;

use Filament\Schemas\Schema;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;

// use Filament\Forms\Concerns\InteractsWithForms;
// use Filament\Forms\Contracts\HasForms;

use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class MatriculaMasiva extends Page implements HasSchemas, HasTable
{
    // use InteractsWithForms;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string $resource = MatriculaResource::class;

    protected string $view = 'filament.resources.matriculas.pages.matricula-masiva';

    public ?array $data = [];
    
    public ?int $horarioSeleccionado = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getTitle(): string
    {
        return 'Matrícula Masiva';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('horario_id')
                    ->label('Horario')
                    ->options(function () {
                        return Horario::with('programa')
                            ->get()
                            ->mapWithKeys(function ($horario) {
                                $programa = $horario->programa->nombre_programa ?? 'Sin programa';
                                $turno = $horario->turno?->value ?? '';
                                $dias = is_array($horario->dias) 
                                    ? implode(', ', $horario->dias) 
                                    : ($horario->dias ?? '');
                                $horarioTexto = $horario->horario ?? '';
                                
                                $label = "{$programa} | Turno: {$turno} | Días: {$dias} | Horario: {$horarioTexto}";
                                
                                return [$horario->id_horario => $label];
                            });
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->horarioSeleccionado = $state;
                    }),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Estudiante::query()
                    ->when($this->horarioSeleccionado, function (Builder $query) {
                        // Excluir estudiantes ya matriculados en este horario
                        $query->whereDoesntHave('matriculas', function (Builder $q) {
                            $q->where('horario_id', $this->horarioSeleccionado);
                        });
                    })
            )
            ->columns([
                TextColumn::make('nro_documento')
                    ->label('DNI')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nombres')
                    ->label('Nombres')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('apellido_paterno')
                    ->label('Apellido Paterno')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('apellido_materno')
                    ->label('Apellido Materno')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('apellido_paterno', 'asc')
            ->bulkActions([
                BulkAction::make('matricular')
                    ->label('Matricular seleccionados')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar matrícula masiva')
                    ->modalDescription(fn ($records) => 
                        'Se matricularán ' . $records->count() . ' estudiante(s) en el horario seleccionado.'
                    )
                    ->action(function ($records) {
                        if (!$this->horarioSeleccionado) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Debe seleccionar un horario primero.')
                                ->send();
                            return;
                        }

                        $horario = Horario::with('programa')->find($this->horarioSeleccionado);
                        
                        if (!$horario) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Horario no encontrado.')
                                ->send();
                            return;
                        }

                        // Determinar tipo de matrícula según el programa
                        $tipoPrograma = $horario->programa->tipo_programa;
                        $tipoMatricula = match($tipoPrograma) {
                            Tip::PROGRAMA           => TipoMatricula::PROGRAMA,
                            Tip::FORMACION_CONTINUA => TipoMatricula::FORMACION_CONTINUA,
                            default                 => TipoMatricula::PROGRAMA,
                        };

                        $matriculasCreadas = 0;
                        $errores = [];
                        
                        foreach ($records as $estudiante) {
                            try {
                                Matricula::create([
                                    'estudiante_id' => $estudiante->id,
                                    'horario_id' => $this->horarioSeleccionado,
                                    'tipo_matricula' => $tipoMatricula,
                                    'estado' => EstadoMatricula::ENPROCESO,
                                    'id_curso' => null,
                                ]);
                                
                                $matriculasCreadas++;
                            } catch (\Exception $e) {
                                // Registrar el error para diagnóstico
                                \Log::error('Error en matrícula masiva', [
                                    'estudiante_id' => $estudiante->id,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                                
                                $errores[] = "Estudiante {$estudiante->nombres}: " . $e->getMessage();
                                continue;
                            }
                        }

                        // Mostrar notificación con resultados
                        if ($matriculasCreadas > 0) {
                            Notification::make()
                                ->success()
                                ->title('Matrícula masiva completada')
                                ->body("Se matricularon {$matriculasCreadas} estudiante(s) exitosamente." . 
                                    (count($errores) > 0 ? "\n\nErrores: " . count($errores) : ''))
                                ->send();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('No se pudo crear ninguna matrícula')
                                ->body('Errores encontrados: ' . implode('; ', array_slice($errores, 0, 3)))
                                ->send();
                        }

                        // Redirigir a la lista de matrículas
                        return redirect()->route('filament.admin.resources.matriculas.index');
                    })
                    ->disabled(fn () => !$this->horarioSeleccionado)
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('No hay estudiantes disponibles')
            ->emptyStateDescription(
                $this->horarioSeleccionado 
                    ? 'Todos los estudiantes ya están matriculados en este horario o no hay estudiantes registrados.'
                    : 'Seleccione un horario para ver los estudiantes disponibles.'
            );
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('volver')
                ->label('Volver')
                ->url(MatriculaResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }
}
