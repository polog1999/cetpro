<?php

namespace App\Filament\Resources\Cronogramas\Tables;

use App\Models\Cronograma;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use App\Enums\EstadoPago;

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;

use Filament\Actions\Action;
use App\Filament\Resources\Cronogramas\CronogramaResource;
use Doctrine\DBAL\Schema\View;
use App\Models\Pago;

class CronogramasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Eager loading para evitar N+1
            ->modifyQueryUsing(
                fn (Builder $query) => $query->with([
                    'matricula.estudiante',
                    'matricula.seccion.programa',
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
                // SECCIÓN + PROGRAMA / CURSO + HORARIO + DÍAS
                // =========================
                TextColumn::make('seccion_info')
                    ->label('Sección / Programa')
                    ->getStateUsing(function (Cronograma $record) {
                        $matricula = $record->matricula;
                        $seccion   = $matricula?->seccion;
                        $curso     = $matricula?->curso;

                        // Si no hay sección ni curso
                        if (! $seccion && ! $curso) {
                            return '-';
                        }

                        // Programa o curso matriculado
                        $principal = $curso?->nombre_curso
                            ?? $seccion?->programa?->nombre_programa
                            ?? 'Sin programa / curso';

                        // Días
                        $dias = $seccion?->dias ?? '';
                        if (is_array($dias)) {
                            $dias = implode(', ', $dias);
                        }

                        // Horario
                        $horario = $seccion?->horario ?? '';

                        $extra = trim($dias . ' ' . $horario);

                        return $extra ? "{$principal} | {$extra}" : $principal;
                    })
                    ->wrap(),   // Solo para que haga salto de línea si es largo
                    // OJO: sin ->searchable() para evitar SQL raro sobre relaciones

                // =========================
                // NÚMERO DE CUOTAS
                // =========================
                TextColumn::make('num_cuotas')
                    ->label('N.º cuotas')
                    ->numeric()
                    ->sortable(),

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
                        $esDeudor = $record->pagos()
                            ->where('estado', EstadoPago::PENDIENTE)
                            ->where('fecha_vencimiento', '<', now())
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
                                $query->where('estado', EstadoPago::PENDIENTE)
                                    ->where('fecha_vencimiento', '<', now());
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
                                $q->where('estado', EstadoPago::PENDIENTE)
                                    ->where('fecha_vencimiento', '<', now());
                            });
                        }

                        if ($data['value'] === 'al_dia') {
                            return $query->whereDoesntHave('pagos', function (Builder $q) {
                                $q->where('estado', EstadoPago::PENDIENTE)
                                    ->where('fecha_vencimiento', '<', now());
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
                ->url(fn (Cronograma $record): string => CronogramaResource::getUrl('view', ['record' => $record])
        ),
])
        

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
