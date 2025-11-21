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
                    ->searchable(),

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
            ])
            ->recordActions([
                // EditAction::make(),
                Action::make('subir_evidencia')
                    ->label('Subir Evidencia')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->color('success')
                    ->visible(fn (Pago $record): bool => $record->estado === \App\Enums\EstadoPago::PENDIENTE)
                    ->form([
                        Select::make('metodo_pago')
                            ->options([
                                'efectivo' => 'Efectivo',
                                'yape' => 'Yape',
                                'plin' => 'Plin',
                                'transferencia' => 'Transferencia',
                            ])
                            ->required()
                            ->label('Método de Pago'),
                        FileUpload::make('evidencia')
                            ->label('Archivo de Evidencia')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->required(),
                    ])
                    ->action(function (Pago $record, array $data): void {
                        // Lógica para guardar la evidencia
                        $fechaActual = now();
                        $record->update([
                            'evidencia' => $data['evidencia'],
                            'metodoPago' => $data['metodo_pago'],
                            'estado' => EstadoPago::PAGADO,
                            'fecha_pago' => $fechaActual,
                        ]);
                        Notification::make()->title('Evidencia subida')->success()->send();
                    }),
                Action::make('editar_evidencia')
                    ->label('Editar evidencia')
                    ->icon('heroicon-o-pencil')
                    ->color('info')
                    ->visible(fn (Pago $record): bool => $record->estado === \App\Enums\EstadoPago::PAGADO)
                    ->form([
                        Select::make('metodo_pago')
                            ->options([
                                'efectivo' => 'Efectivo',
                                'yape' => 'Yape',
                                'plin' => 'Plin',
                                'transferencia' => 'Transferencia',
                            ])
                            ->required()
                            ->label('Método de Pago'),
                        FileUpload::make('evidencia')
                            ->label('Archivo de Evidencia')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->required(),
                    ])
                    ->action(function (Pago $record, array $data): void {
                        // Lógica para guardar la evidencia
                        $fechaActual = now();
                        $record->update([
                            'evidencia' => $data['evidencia'],
                            'metodoPago' => $data['metodo_pago'],
                            'estado' => EstadoPago::PAGADO,
                            'fecha_pago' => $fechaActual, // Registra la fecha de subida
                        ]);
                        Notification::make()->title('Evidencia subida')->success()->send();
                        // $this->verPagos(); Recarga la vista para actualizar la tabla
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
