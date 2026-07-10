<?php

namespace App\Filament\Resources\Cronogramas\Tables;

use App\Filament\Traits\PreventDeleteWithDependencies;
use App\Models\Cronograma;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;

use App\Filament\Resources\Cronogramas\CronogramaResource;
use App\Models\Pago;
use Filament\Actions\Action;

class CronogramasTable
{
    use PreventDeleteWithDependencies;
    public static function configure(Table $table): Table
    {
        return $table
            // Eager loading para evitar N+1
            ->modifyQueryUsing(
                fn (Builder $query) => $query->with([
                    'matricula.estudiante',
                    'matricula.horario.programa',
                    'matricula.curso',
                ])
            )

            ->columns([

                // =========================
                // ALUMNO MATRICULADO
                // =========================
                TextColumn::make('alumno')
                    ->label('Alumno')
                    ->getStateUsing(function (Cronograma $record) {
                        $est = $record->matricula?->estudiante;

                        if (! $est) {
                            return '-';
                        }

                        return trim(
                            "{$est->nombres} {$est->apellido_paterno} {$est->apellido_materno}"
                        );
                    })
                    // Buscador: nombre / apellidos / DNI
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            // Si usas Postgres puedes dejar 'ilike',
                            // si usas MySQL cambia a 'like'
                            return $query->whereHas(
                                'matricula.estudiante',
                                function (Builder $q) use ($search) {
                                    $q->where('nombres', 'ilike', "%{$search}%")
                                        ->orWhere('apellido_paterno', 'ilike', "%{$search}%")
                                        ->orWhere('apellido_materno', 'ilike', "%{$search}%")
                                        ->orWhere('nro_documento', 'ilike', "%{$search}%");
                                }
                            );
                        }
                    ),

                // =========================
                // DNI DEL ALUMNO
                // =========================
                TextColumn::make('dni')
                    ->label('DNI')
                    ->getStateUsing(function (Cronograma $record) {
                        return $record->matricula?->estudiante?->nro_documento ?? '-';
                    })
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->whereHas(
                                'matricula.estudiante',
                                fn (Builder $q) => $q->where('nro_documento', 'ilike', "%{$search}%")
                            );
                        }
                    ),

                // =========================
                // CÓDIGO DEL PROGRAMA (XXX)
                // =========================
                TextColumn::make('codigo_programa')
                    ->label('Código Programa')
                    ->getStateUsing(function (Cronograma $record) {
                        $horario = $record->matricula?->horario;
                        
                        if (!$horario || !$horario->id_programa) {
                            return '-';
                        }
                        
                        // Formatear el ID del programa a 3 dígitos (igual que en el código de matrícula)
                        return str_pad($horario->id_programa, 3, '0', STR_PAD_LEFT);
                    })
                    ->sortable(
                        query: function (Builder $query, string $direction): Builder {
                            return $query->join('matriculas', 'cronogramas.matricula_id', '=', 'matriculas.id')
                                ->join('horarios', 'matriculas.horario_id', '=', 'horarios.id_horario')
                                ->orderBy('horarios.id_programa', $direction);
                        }
                    ),

                // =========================
                // SECCIÓN + PROGRAMA / CURSO + HORARIO + DÍAS
                // =========================
                TextColumn::make('seccion_info')
                    ->label('Horario / Programa')
                    ->getStateUsing(function (Cronograma $record) {
                        $matricula = $record->matricula;
                        $horario   = $matricula?->horario;
                        $curso     = $matricula?->curso;

                        // Si no hay horario ni curso
                        if (! $horario && ! $curso) {
                            return '-';
                        }

                        // Programa o curso matriculado
                        $principal = $curso?->nombre_curso
                            ?? $horario?->programa?->nombre_programa
                            ?? 'Sin programa / curso';

                        // Días
                        $dias = $horario?->dias ?? '';
                        if (is_array($dias)) {
                            $dias = implode(', ', $dias);
                        }

                        // Horario
                        $horarioTexto = $horario?->horario ?? '';

                        $extra = trim($dias . ' ' . $horarioTexto);

                        return $extra ? "{$principal} | {$extra}" : $principal;
                    })
                    ->wrap(),   // Solo para que haga salto de línea si es largo
                    // OJO: sin ->searchable() para evitar SQL raro sobre relaciones

                // =========================
                // MONTO TOTAL
                // =========================
                TextColumn::make('monto_total')
                    ->label('Monto total')
                    ->numeric()
                    ->sortable(),

                // =========================
                // ESTADO DE DEUDA
                // =========================
                TextColumn::make('estado_deuda')
                    ->label('Estado de Deuda')
                    ->badge()
                    ->getStateUsing(function (Cronograma $record): string {
                        // Solo es deudor si tiene pagos con estado VENCIDO
                        $esDeudor = $record->pagos()
                            ->whereRaw("LOWER(estado) LIKE '%vencido%'")
                            ->exists();

                        return $esDeudor ? 'Deudor' : 'Al día';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Deudor' => 'danger',
                        'Al día' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Deudor' => 'heroicon-m-exclamation-circle',
                        'Al día' => 'heroicon-m-check-badge',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withExists([
                            'pagos as tiene_deuda' => function ($query) {
                                $query->whereRaw("LOWER(estado) LIKE '%vencido%'");
                            }
                        ])->orderBy('tiene_deuda', $direction);
                    }),

                // =========================
                // FECHAS
                // =========================
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                // =========================
                // FILTRO POR NOMBRE DE ALUMNO
                // =========================
                SelectFilter::make('alumno')
                    ->label('Alumno')
                    ->searchable()
                    ->multiple()
                    ->options(function (): array {
                        return \App\Models\Estudiante::query()
                            ->orderBy('apellido_paterno')
                            ->get()
                            ->mapWithKeys(function ($estudiante) {
                                $nombreCompleto = trim(
                                    "{$estudiante->nombres} {$estudiante->apellido_paterno} {$estudiante->apellido_materno}"
                                );
                                return [$estudiante->id => $nombreCompleto];
                            })
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('matricula.estudiante', function (Builder $q) use ($data) {
                            $q->whereIn('id', $data['values']);
                        });
                    }),

                // =========================
                // FILTRO POR ESTADO DE DEUDA
                // =========================
                SelectFilter::make('estado_deuda')
                    ->label('Estado de Deuda')
                    ->options([
                        'con_deuda' => 'Deudor',
                        'al_dia' => 'Al día',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        if ($data['value'] === 'con_deuda') {
                            return $query->whereHas('pagos', function (Builder $q) {
                                $q->whereRaw("LOWER(estado) LIKE '%vencido%'");
                            });
                        }

                        if ($data['value'] === 'al_dia') {
                            return $query->whereDoesntHave('pagos', function (Builder $q) {
                                $q->whereRaw("LOWER(estado) LIKE '%vencido%'");
                            });
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                ViewAction::make('verPagos')
                    ->label('Ver Pagos')
                    ->icon('heroicon-m-eye')
                    ->button()
                    ->color('info') 
                    ->url(fn (Cronograma $record): string => CronogramaResource::getUrl('view', ['record' => $record])),
                // EditAction removido porque CronogramaResource::canEdit() = false
                // DeleteAction removido porque CronogramaResource::canDelete() = false
                // Los cronogramas NO se eliminan por integridad financiera
                 Action::make('ver_cronograma_pdf')
                    ->label('Ver PDF')
                     ->icon('heroicon-m-eye')
                    ->button()
                    ->color('success')
                    ->url(fn($record) =>route('ver.cronograma.pdf', ['matricula' => $record->matricula->id]), shouldOpenInNewTab: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                // Bulk actions removidos porque CronogramaResource::canDeleteAny() = false
                // Los cronogramas NUNCA se eliminan por integridad financiera y auditoría
            ]);
    }
}
