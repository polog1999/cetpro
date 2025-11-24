<?php

namespace App\Filament\Resources\Matriculas\Pages;

use App\Filament\Resources\Matriculas\MatriculaResource;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Seccion;
use App\Enums\TipoMatricula;
use App\Enums\EstadoMatricula;
use App\Enums\TipoPrograma;
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
    
    public ?int $seccionSeleccionada = null;

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
                Select::make('seccion_id')
                    ->label('Sección')
                    ->options(function () {
                        return Seccion::with('programa')
                            ->get()
                            ->mapWithKeys(function ($seccion) {
                                $programa = $seccion->programa->nombre_programa ?? 'Sin programa';
                                $turno = $seccion->turno?->value ?? '';
                                $dias = is_array($seccion->dias) 
                                    ? implode(', ', $seccion->dias) 
                                    : ($seccion->dias ?? '');
                                $horario = $seccion->horario ?? '';
                                
                                $label = "{$programa} | Turno: {$turno} | Días: {$dias} | Horario: {$horario}";
                                
                                return [$seccion->id_seccion => $label];
                            });
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->seccionSeleccionada = $state;
                    }),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Estudiante::query()
                    ->when($this->seccionSeleccionada, function (Builder $query) {
                        // Excluir estudiantes ya matriculados en esta sección
                        $query->whereDoesntHave('matriculas', function (Builder $q) {
                            $q->where('seccion_id', $this->seccionSeleccionada);
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
                        'Se matricularán ' . $records->count() . ' estudiante(s) en la sección seleccionada.'
                    )
                    ->action(function ($records) {
                        if (!$this->seccionSeleccionada) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Debe seleccionar una sección primero.')
                                ->send();
                            return;
                        }

                        $seccion = Seccion::with('programa')->find($this->seccionSeleccionada);
                        
                        if (!$seccion) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Sección no encontrada.')
                                ->send();
                            return;
                        }

                        // Determinar tipo de matrícula según el programa
                        $tipoPrograma = $seccion->programa->tipo_programa;
                        $tipoMatricula = match($tipoPrograma) {
                            TipoPrograma::PROGRAMA_ESTUDIO => TipoMatricula::PROG_ESTUDIO,
                            TipoPrograma::FORMACION_CONTINUA => TipoMatricula::FORM_CONTINUA,
                            default => TipoMatricula::PROG_ESTUDIO,
                        };

                        $matriculasCreadas = 0;
                        
                        foreach ($records as $estudiante) {
                            try {
                                Matricula::create([
                                    'estudiante_id' => $estudiante->id,
                                    'seccion_id' => $this->seccionSeleccionada,
                                    'tipo_matricula' => $tipoMatricula,
                                    'estado' => EstadoMatricula::ENPROCESO,
                                    'id_curso' => null,
                                ]);
                                
                                $matriculasCreadas++;
                            } catch (\Exception $e) {
                                // Continuar con el siguiente si hay error
                                continue;
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title('Matrícula masiva completada')
                            ->body("Se matricularon {$matriculasCreadas} estudiante(s) exitosamente.")
                            ->send();

                        // Redirigir a la lista de matrículas
                        return redirect()->route('filament.admin.resources.matriculas.index');
                    })
                    ->disabled(fn () => !$this->seccionSeleccionada)
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('No hay estudiantes disponibles')
            ->emptyStateDescription(
                $this->seccionSeleccionada 
                    ? 'Todos los estudiantes ya están matriculados en esta sección o no hay estudiantes registrados.'
                    : 'Seleccione una sección para ver los estudiantes disponibles.'
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
