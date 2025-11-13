<?php

namespace App\Filament\Resources\Pagos\Pages;

use App\Enums\EstadoPago;
use App\Filament\Resources\Pagos\PagoResource;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Pago;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GestionarPagos extends ListRecords
{
    protected static string $resource = PagoResource::class;
    protected static ?string $title = 'Generador de Pagos';

    /* ---------------------------
     |  Query base de la tabla
     * --------------------------*/
    public function getTableQuery(): Builder
    {
        return Pago::query()->with(['matricula.seccion.modulo']);
    }

    /* ---------------------------
     |  Filtros (los 2 selects)
     * --------------------------*/
    protected function getTableFilters(): array
    {
        return [
            // Select 1: Estudiante
            SelectFilter::make('estudiante_id')
                ->label('Estudiante')
                ->options(fn () => Estudiante::query()
                    ->orderBy('nombres')
                    ->pluck('nombres', 'id'))
                ->live()
                ->query(function (Builder $query, $value) {
                    if ($value) {
                        $query->whereHas('matricula', fn ($q) =>
                            $q->where('estudiante_id', $value)
                        );
                    }
                }),

            // Select 2: Matrícula (depende del estudiante)
            SelectFilter::make('matricula_id')
                ->label('Sección (Matrícula)')
                ->options(function (callable $get) {
                    $estId = $get('estudiante_id');
                    if (! $estId) return [];
                    return Matricula::where('estudiante_id', $estId)
                        ->with('seccion')
                        ->get()
                        ->pluck('seccion.nombre_completo', 'id')
                        ->toArray();
                })
                ->live()
                ->query(fn (Builder $query, $value) =>
                    $value ? $query->where('matricula_id', $value) : $query
                ),
        ];
    }

    /* ---------------------------
     |  Columnas de la tabla
     * --------------------------*/
    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('codigo')->label('Código')->searchable(),
            TextColumn::make('matricula.seccion.modulo.nombre')->label('Módulo')->wrap(),
            TextColumn::make('fecha_vencimiento')->label('Vence')->date()->sortable(),
            TextColumn::make('monto')->label('Monto')->money('PEN')->sortable(),
            BadgeColumn::make('estado')
                ->label('Estado')
                ->formatStateUsing(fn ($s) => ucfirst(strtolower((string) $s)))
                ->colors([
                    'warning' => fn ($s) => (string)$s === (string) EstadoPago::PENDIENTE->value,
                    'success' => fn ($s) => (string)$s === 'pagado',
                    'danger'  => fn ($s) => (string)$s === 'anulado',
                ]),
            TextColumn::make('metodo_pago')->label('Método'),
            TextColumn::make('fecha_pago')->label('Fecha pago')->date(),
        ];
    }

    /* ---------------------------
     |  Botones del header
     * --------------------------*/
    protected function getHeaderActions(): array
    {
        return [
            Action::make('crearOrdenes')
                ->label('Crear órdenes')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    // Lee el filtro seleccionado
                    $filters = $this->getTableFiltersForm()->getState();
                    $matriculaId = $filters['matricula_id'] ?? null;

                    if (! $matriculaId) {
                        Notification::make()
                            ->title('Selecciona Estudiante y Matrícula')
                            ->warning()->send();
                        return;
                    }

                    $matricula = Matricula::with(['seccion.modulo', 'pagos'])->find($matriculaId);

                    if (! $matricula?->seccion?->fecha_inicio || ! $matricula?->seccion?->fecha_fin) {
                        Notification::make()
                            ->title('Fechas incompletas en la Sección')
                            ->danger()->send();
                        return;
                    }

                    DB::transaction(function () use ($matricula) {
                        $seccion = $matricula->seccion;
                        $monto   = (float) ($seccion->modulo->costo ?? 0);

                        $cursor = Carbon::parse($seccion->fecha_inicio)->startOfMonth();
                        $hasta  = Carbon::parse($seccion->fecha_fin)->endOfMonth();

                        while ($cursor->lte($hasta)) {
                            $venc = $cursor->copy()->endOfMonth();

                            $existe = $matricula->pagos()
                                ->whereMonth('fecha_vencimiento', $venc->month)
                                ->whereYear('fecha_vencimiento', $venc->year)
                                ->exists();

                            if (! $existe) {
                                Pago::create([
                                    'matricula_id'      => $matricula->id,
                                    'monto'             => $monto,
                                    'estado'            => EstadoPago::PENDIENTE,
                                    'fecha_vencimiento' => $venc->toDateString(),
                                    'metodo_pago'       => null,
                                    'fecha_pago'        => null,
                                    'evidencia'         => null,
                                ]);
                            }

                            $cursor->addMonth()->startOfMonth();
                        }
                    });

                    // refresca la tabla
                    $this->resetTable();

                    Notification::make()
                        ->title('Órdenes generadas')
                        ->success()->send();
                }),

            Action::make('verCargos')
                ->label('Ver cargos')
                ->icon('heroicon-o-credit-card')
                ->color('gray')
                ->action(fn () => null),
        ];
    }
}
