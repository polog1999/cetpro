<?php

namespace App\Filament\Resources\Cronogramas\Tables;

use App\Models\Cronograma;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                //
            ])

            // Sin acciones por registro (no editar)
            ->recordActions([
                //
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
