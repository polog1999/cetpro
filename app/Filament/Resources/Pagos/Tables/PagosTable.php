<?php

namespace App\Filament\Resources\Pagos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;
// use Filament\Schemas\Components\Utilities\Get;
// use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;

use Filament\Tables\Filters\Filter;


use Illuminate\Support\Facades\Storage;
use Filament\Tables\Filters\SelectFilter;


use App\Models\Programa;
use App\Models\Horario;
use App\Models\Curso;

use App\Models\Pago;
use App\Models\Estudiante;

class PagosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // 🆕 Estudiante
                TextColumn::make('nro_cuota')
                    ->label('Nro de recibo')
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('cronograma.matricula.estudiante.nro_documento')
                    ->label('DNI')
                    ->sortable()
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->whereHas(
                                'cronograma.matricula.estudiante',
                                fn (Builder $q) => $q->where('nro_documento', 'ilike', "%{$search}%")
                            );
                        },
                    ),
                    
                TextColumn::make('cronograma.matricula.estudiante.nombre_completo')
                    ->label('Estudiante')
                    ->sortable()
                    ->searchable(
                        // Búsqueda personalizada sobre DNI y nombre
                        query: function (Builder $query, string $search): Builder {
                            return $query->whereHas(
                                'cronograma.matricula.estudiante',
                                function (Builder $q) use ($search) {
                                    $q->where(function (Builder $q2) use ($search) {
                                        $q2->where('nro_documento', 'ilike', "%{$search}%")
                                            ->orWhere('nombres', 'ilike', "%{$search}%")
                                            ->orWhere('apellido_paterno', 'ilike', "%{$search}%")
                                            ->orWhere('apellido_materno', 'ilike', "%{$search}%");
                                    });
                                }
                            );
                        },
                    ),

                TextColumn::make('monto')
                    ->label('Monto')
                    ->numeric()
                    ->sortable(),
                    
                TextColumn::make('num_liquidacion')
                    ->searchable(),
                TextColumn::make('estado')
                    ->label('Estado de Pago')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains(strtolower($state), 'cancelado') => 'success',
                        str_contains(strtolower($state), 'pendiente') => 'warning',
                        str_contains(strtolower($state), 'vencido') => 'danger',
                        str_contains(strtolower($state), 'anulado') => 'gray',
                        default => 'info',
                    }),

                TextColumn::make('cronograma.matricula.detalle_matricula')
                    ->label('Detalle Matrícula')
                    ->getStateUsing(function ($record) {
                        $matricula = $record->cronograma?->matricula;
                        if (!$matricula) {
                            return 'Sin matrícula';
                        }
                        
                        $tipo = $matricula->tipo_matricula?->value ?? $matricula->tipo_matricula ?? '';
                        
                        // Para UNIDAD: mostrar nombre de la unidad
                        if ($tipo === 'Unidad' && $matricula->unidad) {
                            return $matricula->unidad->nombre_unidad;
                        }
                        
                        // Para CURSO o MODULO: mostrar nombre del curso
                        if (in_array($tipo, ['Curso', 'Módulo']) && $matricula->curso) {
                            return $matricula->curso->nombre_curso;
                        }
                        
                        // Para PROGRAMA o FORMACION_CONTINUA: mostrar nombre del programa
                        if (in_array($tipo, ['Programa', 'Formación continua']) && $matricula->horario?->programa) {
                            return $matricula->horario->programa->nombre_programa;
                        }
                        
                        return 'Sin detalle';
                    })
                    ->wrap()
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->where(function (Builder $q) use ($search) {
                                // Buscar en nombre de programa
                                $q->whereHas('cronograma.matricula.horario.programa', function ($subQ) use ($search) {
                                    $subQ->where('nombre_programa', 'ilike', "%{$search}%");
                                })
                                // Buscar en nombre de curso
                                ->orWhereHas('cronograma.matricula.curso', function ($subQ) use ($search) {
                                    $subQ->where('nombre_curso', 'ilike', "%{$search}%");
                                })
                                // Buscar en nombre de unidad
                                ->orWhereHas('cronograma.matricula.unidad', function ($subQ) use ($search) {
                                    $subQ->where('nombre_unidad', 'ilike', "%{$search}%");
                                });
                            });
                        },
                    ),

                TextColumn::make('fecha_vencimiento')
                    ->label('Fecha de vencimiento')
                    ->date()
                    ->sortable(),
                    
                    
                TextColumn::make('fecha_liquidacion')
                    ->label('Fecha emisión')
                    ->date()
                    ->sortable(),

                TextColumn::make('fecha_pago')
                    ->label('Fecha de pago')
                    ->date()
                    ->sortable(),

                TextColumn::make('created_at')
                     ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
    
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // ------------------ PROGRAMA ------------------
            Filter::make('estructura')
                ->label('Programa / Horario / Curso')
                ->form([
                    // ---------------- PROGRAMA ----------------
                    Select::make('programa_id')
                        ->label('Programa')
                        ->options(fn () =>
                            Programa::query()
                                ->whereNotNull('nombre_programa')
                                ->orderBy('nombre_programa')
                                ->pluck('nombre_programa', 'id_programa')
                                ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                // Si elijo un programa, limpio horario y curso
                                $set('horario_id', null);
                                $set('curso_id', null);
                            }
                        }),

                    // ---------------- HORARIO -----------------
                    Select::make('horario_id')
                        ->label('Horario')
                        ->options(fn () =>
                            Horario::query()
                                ->with('programa')
                                ->get()
                                ->mapWithKeys(function (Horario $horario) {
                                    $programa = $horario->programa->nombre_programa ?? 'Sin programa';

                                    $texto = $programa
                                        .' | Hor. '.$horario->id_horario
                                        .' | Aula '.$horario->aula;

                                    return [
                                        $horario->id_horario => $texto,
                                    ];
                                })
                                ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                // Si elijo horario, limpio programa y curso
                                $set('programa_id', null);
                                $set('curso_id', null);
                            }
                        }),

                    // ---------------- CURSO -------------------
                    Select::make('curso_id')
                        ->label('Curso')
                        ->options(fn () =>
                            Curso::query()
                                ->whereNotNull('nombre_curso')
                                ->orderBy('nombre_curso')
                                ->pluck('nombre_curso', 'id_curso')
                                ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                // Si elijo curso, limpio programa y horario
                                $set('programa_id', null);
                                $set('horario_id', null);
                            }
                        }),
                ])
                ->query(function (Builder $query, array $data): Builder {

                    if (! empty($data['programa_id'])) {
                        return $query->whereHas(
                            'cronograma.matricula.horario',
                            fn (Builder $q) => $q->where('id_programa', $data['programa_id'])
                        );
                    }

                    if (! empty($data['horario_id'])) {
                        return $query->whereHas(
                            'cronograma.matricula',
                            fn (Builder $q) => $q->where('horario_id', $data['horario_id'])
                        );
                    }

                    if (! empty($data['curso_id'])) {
                        return $query->whereHas(
                            'cronograma.matricula',
                            fn (Builder $q) => $q->where('id_curso', $data['curso_id'])
                        );
                    }

                    return $query;
                })
                ->indicateUsing(function (array $data): ?string {
                    if (! empty($data['programa_id'])) {
                        $prog = Programa::find($data['programa_id']);
                        return $prog ? 'Programa: '.$prog->nombre_programa : null;
                    }

                    if (! empty($data['horario_id'])) {
                        $hor = Horario::find($data['horario_id']);
                        if (! $hor) {
                            return null;
                        }

                        $programa = $hor->programa->nombre_programa ?? 'Sin programa';

                        return 'Horario: '.$programa.' - Hor. '.$hor->id_horario;
                    }

                    if (! empty($data['curso_id'])) {
                        $curso = Curso::find($data['curso_id']);
                        return $curso ? 'Curso: '.$curso->nombre_curso : null;
                    }

                    return null;
                }),

                // Filtro por estudiante
                SelectFilter::make('estudiante')
                    ->label('Estudiante')
                    ->options(fn () =>
                        Estudiante::query()
                            ->whereHas('matriculas.cronograma.pagos')
                            ->get()
                            ->mapWithKeys(fn (Estudiante $est) => [
                                $est->id => $est->nombre_completo . ' - ' . $est->nro_documento
                            ])
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->query(fn (Builder $query, array $data): Builder =>
                        ! empty($data['value'])
                            ? $query->whereHas(
                                'cronograma.matricula',
                                fn (Builder $q) => $q->where('estudiante_id', $data['value'])
                            )
                            : $query
                    ),
            ])


        ->recordActions([
                Action::make('ver_evidencia')
                    ->label('')
                    ->tooltip('Ver evidencia')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (Pago $record): bool => filled($record->evidencia_path))
                    ->url(fn (Pago $record): string => Storage::disk('public')->url($record->evidencia_path))
                    ->openUrlInNewTab(),

                Action::make('subir_evidencia')
                    ->label('')
                    ->tooltip('Subir evidencia')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->color('success')
                    ->visible(fn (Pago $record): bool => empty($record->evidencia_path))
                    ->form([
                        Select::make('metodo_pago')
                            ->options([
                                'efectivo'      => 'Efectivo',
                                'yape'          => 'Yape',
                                'plin'          => 'Plin',
                                'transferencia' => 'Transferencia',
                            ])
                            ->required()
                            ->label('Método de Pago'),
                        FileUpload::make('evidencia')
                            ->label('Archivo de Evidencia')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->disk('public')                      // 👈 importante
                            ->directory('pagos/evidencias')      // 👈 carpeta donde se guarda
                            ->required(),
                    ])
                    ->action(function (Pago $record, array $data): void {
                        $service = app(\App\Services\PagoService::class);
                        
                        try {
                            // Delegar toda la lógica al servicio
                            $service->registrarPago(
                                pago: $record,
                                metodoPago: $data['metodo_pago'],
                                evidenciaPath: $data['evidencia'],
                                usuarioId: auth()->id()
                            );
                            
                            Notification::make()->title('Evidencia subida')->success()->send();
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            Notification::make()
                                ->title('Error al registrar el pago')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('editar_evidencia')
                    ->label('Editar evidencia')
                    ->icon('heroicon-o-pencil')
                    ->color('info')
                    ->visible(fn (Pago $record): bool => filled($record->evidencia_path))
                    ->form([
                        Select::make('metodo_pago')
                            ->options([
                                'efectivo'      => 'Efectivo',
                                'yape'          => 'Yape',
                                'plin'          => 'Plin',
                                'transferencia' => 'Transferencia',
                            ])
                            ->required()
                            ->label('Método de Pago'),
                        FileUpload::make('evidencia')
                            ->label('Archivo de Evidencia')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->disk('public')
                            ->directory('pagos/evidencias')
                            ->required(),
                    ])
                    ->action(function (Pago $record, array $data): void {
                        $service = app(\App\Services\PagoService::class);
                        
                        try {
                            // Usar el servicio para actualizar el pago
                            $service->registrarPago(
                                pago: $record,
                                metodoPago: $data['metodo_pago'],
                                evidenciaPath: $data['evidencia'],
                                usuarioId: auth()->id()
                            );
                            
                            Notification::make()->title('Evidencia actualizada')->success()->send();
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            Notification::make()
                                ->title('Error al actualizar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('anular_pago')
                    ->label('')
                    ->tooltip('Anular pago')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Pago $record): bool => 
                        strtolower($record->estado) === 'pendiente' && 
                        auth()->user()->esAdmin()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('¿Seguro que desea anular este pago?')
                    ->modalSubheading('Esta acción primero anulará el pago en Oracle y luego en PostgreSQL. No se puede revertir.')
                    ->modalButton('Confirmar anulación')
                    ->action(function (Pago $record) {
                        $service = app(\App\Services\PagoService::class);
                        try {
                            $service->anularPago($record);
                            \Filament\Notifications\Notification::make()
                                ->title('Pago anulado correctamente')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error al anular pago')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
        ])
        ->toolbarActions([
                
        ]);
    }
}
