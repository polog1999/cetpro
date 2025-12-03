<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

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

                TextColumn::make('permisos')
                    ->label('Permisos')
                    ->formatStateUsing(function ($record) {
                        if ($record->es_admin) {
                            return 'Acceso Total';
                        }
                        $count = $record->permisos()->count();
                        return $count . ' ' . ($count === 1 ? 'permiso' : 'permisos');
                    })
                    ->badge()
                    ->color(fn ($record) => $record->es_admin ? 'success' : 'warning'),

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
