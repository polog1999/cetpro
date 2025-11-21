<?php

namespace App\Filament\Resources\Pagos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // 🆕 Estudiante
                TextColumn::make('cronograma.matricula.estudiante.nombre_completo')
                    ->label('Estudiante')
                    ->sortable()
                    ->searchable(),

                // 🆕 Sección
                TextColumn::make('cronograma.matricula.seccion.nombre_completo')
                    ->label('Sección')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('cronograma.id')
                    ->label('Cronograma')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('nro_cuota')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('codigo')
                    ->searchable(),

                TextColumn::make('monto')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('estado')
                    ->searchable(),

                TextColumn::make('fecha_vencimiento')
                    ->date()
                    ->sortable(),

                TextColumn::make('fecha_pago')
                    ->date()
                    ->sortable(),

                TextColumn::make('metodo_pago')
                    ->searchable(),

                TextColumn::make('evidencia_path')
                    ->searchable(),

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
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
