<?php

namespace App\Filament\Resources\Pagos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;


use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use App\Enums\EstadoPago;
use App\Models\Pago;

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
                // EditAction::make(),
                Action::make('subir_evidencia')
                    ->label('Subir Evidencia')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->color('success')
                    ->form([
                        Select::make('metodo_pago')
                            ->options([
                                'efectivo'=>'Efectivo',
                                'transferencia'=>'Transferencia',
                                'Yape/Plin'=>'Yape/Plin',
                            ])
                            ->label('Método de pago'),
                        FileUpload::make('evidencia_path')
                            ->label('Archivo de evidencia')
                            ->acceptedFileTypes(['applications/pdf', 'image/*'])
                            ->required()
                    ])
                    ->action(function(Pago $record, array $data):void{
                        $fechaActual = now();
                        $record->update([
                            'evidencia_path'=>$data['evidencia'],
                            'metodo_pago'=>$data['metodo_pago'],
                            'estado'=>EstadoPago::PAGADO,
                            'fecha_pago'=>$fechaActual,
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
