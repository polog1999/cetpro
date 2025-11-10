<?php

namespace App\Filament\Resources\Pagos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\Tables\Filters\Layout;
use Filament\Tables\Filters\SelectFilter;

use Illuminate\Database\Eloquent\Builder; // Para la consulta

use App\Models\Estudiante;
use App\Models\Seccion;
use App\Models\Matricula;

class PagosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('codigo')
                //     ->searchable(),
                TextColumn::make('matricula.estudiante.nombres')
                    ->label('Estudiante'),
                TextColumn::make('matricula.seccion.nombre_completo') 
                    ->label('Sección'),
                TextColumn::make('monto')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estado')
                    ->searchable(),
                TextColumn::make('fecha_vencimiento')
                    ->date()
                    ->sortable(),
                TextColumn::make('metodo_pago')
                    ->searchable(),
                TextColumn::make('fecha_pago')
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
                SelectFilter::make('estudiante')
                    ->label('Estudiante')
                    ->options(Estudiante::all()->pluck('nombres', 'id'))
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'])) {
                            return $query;
                        }
                        // Filtra los Pagos que pertenecen a una Matrícula 
                        // que pertenece al Estudiante seleccionado
                        return $query->whereHas('matricula', fn($q) => 
                            $q->where('estudiante_id', $data['value'])
                        );
                    }),
                
                // --- FILTRO 2: SECCIÓN (Independiente) ---
                SelectFilter::make('seccion')
                    ->label('Sección')
                    ->options(Seccion::all()->pluck('nombre_completo', 'id')) // Usa el accesor
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'])) {
                            return $query;
                        }
                        // Filtra los Pagos que pertenecen a una Matrícula 
                        // que pertenece a la Sección seleccionada
                        return $query->whereHas('matricula', fn($q) => 
                            $q->where('seccion_id', $data['value'])
                        );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
