<?php

namespace App\Filament\Resources\Usuarios\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsuariosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('empleado_nombre_completo')
                    ->label('Empleado')
                    ->getStateUsing(function ($record) {
                        $emp = $record->empleado;
                        return $emp ? "{$emp->nombre} {$emp->apellido_paterno} {$emp->apellido_materno}" : '-';
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('empleado', function ($q) use ($search) {
                            $q->where('nombre', 'ilike', "%{$search}%")
                              ->orWhere('apellido_paterno', 'ilike', "%{$search}%")
                              ->orWhere('apellido_materno', 'ilike', "%{$search}%");
                        });
                    }),
                TextColumn::make('usuario')
                    ->searchable(),
                TextColumn::make('role.nombre')
                    ->label('Rol')
                    ->searchable()
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Admin' => 'success',
                        'Secretaria' => 'info',
                        default => 'gray',
                    }),
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
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
