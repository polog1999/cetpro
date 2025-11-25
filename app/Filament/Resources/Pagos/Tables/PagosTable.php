<?php

namespace App\Filament\Resources\Pagos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;
// use Filament\Schemas\Components\Utilities\Get;
// use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;

use Filament\Tables\Filters\Filter;


use Illuminate\Support\Facades\Storage;
use Filament\Tables\Filters\SelectFilter;


use App\Models\Programa;
use App\Models\Seccion;
use App\Models\Curso;

use App\Enums\EstadoPago;
use App\Models\Pago;

class PagosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // 🆕 Estudiante
                TextColumn::make('nro_cuota')
                    ->label('Nro. de cuota')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cronograma.matricula.estudiante.nombre_completo')
                    ->label('Estudiante')
                    ->sortable()
                    ->searchable(
        // Búsqueda personalizada sobre columnas reales
        query: function (Builder $query, string $search): Builder {
            return $query->whereHas(
                'cronograma.matricula.estudiante',
                function (Builder $q) use ($search) {
                    $q->where(function (Builder $q2) use ($search) {
                        $q2->where('nombres', 'ilike', "%{$search}%")
                            ->orWhere('apellido_paterno', 'ilike', "%{$search}%")
                            ->orWhere('apellido_materno', 'ilike', "%{$search}%");
                    });
                }
            );
        },
    ),

                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable(),

                TextColumn::make('monto')
                    ->label('Monto')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->searchable(),

                TextColumn::make('fecha_vencimiento')
                    ->label('Fecha de vencimiento')
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

                TextColumn::make('num_liquidacion')
                    ->searchable(),

                TextColumn::make('fecha_liquidacion')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                // ------------------ PROGRAMA ------------------
    Filter::make('estructura')
        ->label('Programa / Sección / Curso')
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
                        // Si elijo un programa, limpio sección y curso
                        $set('seccion_id', null);
                        $set('curso_id', null);
                    }
                }),

            // ---------------- SECCIÓN -----------------
            Select::make('seccion_id')
                ->label('Sección')
                ->options(fn () =>
                    Seccion::query()
                        ->with('programa')
                        ->get()
                        ->mapWithKeys(function (Seccion $seccion) {
                            $programa = $seccion->programa->nombre_programa ?? 'Sin programa';

                            $texto = $programa
                                .' | Sec. '.$seccion->id_seccion
                                .' | Aula '.$seccion->aula;

                            return [
                                $seccion->id_seccion => $texto,
                            ];
                        })
                        ->toArray()
                )
                ->searchable()
                ->preload()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                        // Si elijo sección, limpio programa y curso
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
                        // Si elijo curso, limpio programa y sección
                        $set('programa_id', null);
                        $set('seccion_id', null);
                    }
                }),
        ])
        ->query(function (Builder $query, array $data): Builder {

            if (! empty($data['programa_id'])) {
                return $query->whereHas(
                    'cronograma.matricula.seccion',
                    fn (Builder $q) => $q->where('id_programa', $data['programa_id'])
                );
            }

            if (! empty($data['seccion_id'])) {
                return $query->whereHas(
                    'cronograma.matricula',
                    fn (Builder $q) => $q->where('seccion_id', $data['seccion_id'])
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

            if (! empty($data['seccion_id'])) {
                $sec = Seccion::find($data['seccion_id']);
                if (! $sec) {
                    return null;
                }

                $programa = $sec->programa->nombre_programa ?? 'Sin programa';

                return 'Sección: '.$programa.' - Sec. '.$sec->id_seccion;
            }

            if (! empty($data['curso_id'])) {
                $curso = Curso::find($data['curso_id']);
                return $curso ? 'Curso: '.$curso->nombre_curso : null;
            }

            return null;
        }),
    ])


        ->recordActions([
                Action::make('ver_evidencia')
    ->label('Ver evidencia')
    ->icon('heroicon-o-eye')
    ->color('gray')
    ->visible(fn (Pago $record): bool => filled($record->evidencia_path))
    ->url(fn (Pago $record): string => route('pagos.evidencia.show', $record))
    ->openUrlInNewTab(),

        Action::make('subir_evidencia')
    ->label('Subir Evidencia')
    ->icon('heroicon-o-arrow-up-on-square')
    ->color('success')
    ->visible(fn (Pago $record): bool => $record->estado === EstadoPago::PENDIENTE)
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
        $fechaActual = now();

        $record->update([
            'evidencia_path' => $data['evidencia'],   // 👈 guardar ruta
            'metodo_pago'    => $data['metodo_pago'], // 👈 nombre correcto
            'estado'         => EstadoPago::PAGADO,
            'fecha_pago'     => $fechaActual,
        ]);

        Notification::make()->title('Evidencia subida')->success()->send();
    }),

Action::make('editar_evidencia')
    ->label('Editar evidencia')
    ->icon('heroicon-o-pencil')
    ->color('info')
    ->visible(fn (Pago $record): bool => $record->estado === EstadoPago::PAGADO)
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
        $fechaActual = now();

        $record->update([
            'evidencia_path' => $data['evidencia'],
            'metodo_pago'    => $data['metodo_pago'],
            'estado'         => EstadoPago::PAGADO,
            'fecha_pago'     => $fechaActual,
        ]);

        Notification::make()->title('Evidencia actualizada')->success()->send();
    }),

                
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),

                // 👇 Nueva acción
    
            ]);
    }
}
