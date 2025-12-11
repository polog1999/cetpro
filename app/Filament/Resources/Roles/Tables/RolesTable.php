<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre del Rol')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                IconColumn::make('es_admin')
                    ->label('Admin')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('usuarios_count')
                    ->label('Usuarios')
                    ->counts('usuarios')
                    ->badge()
                    ->color('info'),

                TextColumn::make('permisos.nombre')
                    ->label('Permisos')
                    ->badge()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->es_admin) {
                            return 'Acceso Total';
                        }
                        return $state;
                    })
                    ->color(fn ($record) => $record->es_admin ? 'success' : 'warning')
                    ->wrap(),

                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->wrap()
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, $record) {
                        // Verificar si el rol tiene usuarios
                        $cantidadUsuarios = $record->usuarios()->count();
                        
                        if ($cantidadUsuarios > 0) {
                            // Cancelar la acción y mostrar notificación
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('No se puede eliminar el rol')
                                ->body("Este rol tiene {$cantidadUsuarios} usuario(s) asignado(s). Para eliminarlo, primero debe reasignar o eliminar estos usuarios.")
                                ->persistent()
                                ->send();
                            
                            // Cancelar la eliminación
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, $records) {
                            // Verificar si alguno de los roles seleccionados tiene usuarios
                            $rolesConUsuarios = [];
                            
                            foreach ($records as $record) {
                                $cantidadUsuarios = $record->usuarios()->count();
                                if ($cantidadUsuarios > 0) {
                                    $rolesConUsuarios[] = "{$record->nombre} ({$cantidadUsuarios} usuarios)";
                                }
                            }
                            
                            if (!empty($rolesConUsuarios)) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('No se pueden eliminar algunos roles')
                                    ->body('Los siguientes roles tienen usuarios asignados: ' . implode(', ', $rolesConUsuarios))
                                    ->persistent()
                                    ->send();
                                
                                // Cancelar la eliminación
                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }
}
