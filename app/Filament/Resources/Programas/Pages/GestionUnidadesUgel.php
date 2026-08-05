<?php

namespace App\Filament\Resources\Programas\Pages;

use App\Enums\TipoPrograma;
use App\Filament\Resources\Programas\ProgramaResource;
use App\Models\Curso;
use App\Models\Programa;
use App\Models\UnidadDidacticaUgel;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Form;
use Filament\Tables\Filters\SelectFilter;

use function Laravel\Prompts\text;

class GestionUnidadesUgel extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = ProgramaResource::class;
    protected string $view = 'filament.resources.programas.pages.gestion-unidades-ugel';

    public $record;

    public function mount(int|string $record): void
    {
        $this->record = Programa::where('id_programa', $record)->firstOrFail();
    }

    public function getTitle(): string
    {
        return "Unidades Didácticas UGEL - {$this->record->nombre_programa}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('crearUnidad')
                ->label('Nueva Unidad Didáctica')
                ->icon('heroicon-m-plus')
                ->color('primary')
                ->button()
                ->form([
                    TextInput::make('nombre')
                        ->label('Nombre de la Unidad Didáctica')
                        ->required()
                        ->maxLength(255),

                    Select::make('curso_id')
                        ->label('Asociar a Módulo / Curso (Opcional)')
                        ->options(
                            \App\Models\Curso::where('id_programa', $this->record->id_programa)
                                ->pluck('nombre_curso', 'id_curso')
                        )
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    TextInput::make('creditos')
                        ->label('Créditos')
                        ->numeric()
                        ->nullable(),

                    TextInput::make('horas')
                        ->label('Horas')
                        ->numeric()
                        ->nullable(),

                    Textarea::make('capacidad')
                        ->label('Capacidad / Competencia')
                        ->rows(3)
                        ->maxLength(500)
                        ->nullable(),
                    TextInput::make('orden')
                        ->label('Orden')
                        ->numeric(),
                    Toggle::make('es_efsrt')
                        ->label('¿Es Experiencia Formativa en Situación Real de Trabajo (EFSRT)?')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    UnidadDidacticaUgel::create([
                        'nombre' => $data['nombre'],
                        'programa_id' => $this->record->id_programa,
                        'curso_id' => $data['curso_id'] ?? null,
                        'creditos' => $data['creditos'] ?? null,
                        'horas' => $data['horas'] ?? null,
                        'capacidad' => $data['capacidad'] ?? null,
                        'orden' => $data['orden'] ?? null,
                        'es_efsrt' => $data['es_efsrt'] ?? false,
                    ]);

                    Notification::make()
                        ->title('Unidad creada exitosamente')
                        ->success()
                        ->send();
                }),
        ];
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(
                UnidadDidacticaUgel::query()->where('programa_id', $this->record->id_programa)
            )
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre de la Unidad')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('curso.nombre_curso')
                    ->label('Módulo / Curso Asociado')
                    ->placeholder('N/A (General)')
                    ->sortable(),

                TextColumn::make('creditos')
                    ->label('Créditos')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('horas')
                    ->label('Horas')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('es_efsrt')
                    ->label('¿Es EFSRT?')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
               SelectFilter::make('curso_id')
        ->label('Curso')
        ->options(function () {
            // Verificamos que la página tenga un registro cargado
            if ($this->record) {
                // Obtenemos el ID del programa actual (ajusta 'id_programa' o 'id' según tu base de datos)
                $programaId = $this->record->id_programa ?? $this->record->id;

                // Buscamos los IDs de los cursos que pertenecen a este programa en la tabla actual
                $cursoIds = UnidadDidacticaUgel::where('programa_id', $programaId)
                    ->pluck('curso_id')
                    ->unique();

                // Retornamos solo los nombres e IDs de esos cursos
                return Curso::whereIn('id_curso', $cursoIds)
                    ->pluck('nombre_curso', 'id_curso');
            }

            return Curso::pluck('nombre_curso', 'id_curso');
        })
        ->searchable()
        ->preload()
            ])
            ->recordActions([
                // 👉 REEMPLAZAMOS EditAction por una Action personalizada en modo Modal
                Action::make('editar')
                    ->label('Editar')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->button()
                    ->mountUsing(fn($form, UnidadDidacticaUgel $record) => $form->fill([
                        'nombre' => $record->nombre,
                        'curso_id' => $record->curso_id,
                        'creditos' => $record->creditos,
                        'horas' => $record->horas,
                        'capacidad' => $record->capacidad,
                        'orden' => $record->orden,
                        'es_efsrt' => $record->es_efsrt,
                    ]))
                    ->form([
                        TextInput::make('nombre')
                            ->label('Nombre de la Unidad Didáctica')
                            ->required()
                            ->maxLength(255),

                        Select::make('curso_id')
                            ->label('Asociar a Módulo / Curso (Opcional)')
                            ->options(
                                \App\Models\Curso::where('id_programa', $this->record->id_programa)
                                    ->pluck('nombre_curso', 'id_curso')
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        TextInput::make('creditos')
                            ->label('Créditos')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('horas')
                            ->label('Horas')
                            ->numeric()
                            ->nullable(),

                        Textarea::make('capacidad')
                            ->label('Capacidad / Competencia')
                            ->rows(3)
                            ->maxLength(500)
                            ->nullable(),

                        TextInput::make('orden')
                            ->label('Orden')
                            ->numeric(),

                        Toggle::make('es_efsrt')
                            ->label('¿Es Experiencia Formativa (EFSRT)?')
                            ->default(false),
                    ])
                    ->action(function (UnidadDidacticaUgel $record, array $data): void {
                        $record->update([
                            'nombre' => $data['nombre'],
                            'curso_id' => $data['curso_id'] ?? null,
                            'creditos' => $data['creditos'] ?? null,
                            'horas' => $data['horas'] ?? null,
                            'capacidad' => $data['capacidad'] ?? null,
                            'orden' => $data['orden'] ?? null,
                            'es_efsrt' => $data['es_efsrt'] ?? false,
                        ]);

                        Notification::make()
                            ->title('Unidad actualizada exitosamente')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
