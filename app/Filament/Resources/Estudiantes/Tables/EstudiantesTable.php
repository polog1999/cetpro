<?php

namespace App\Filament\Resources\Estudiantes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EstudiantesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nro_documento')
                    ->searchable(),
                TextColumn::make('nombres')
                    ->searchable(),
                TextColumn::make('apellido_paterno')
                    ->searchable(),
                TextColumn::make('apellido_materno')
                    ->searchable(),
                TextColumn::make('genero')
                    ->searchable(),
                TextColumn::make('estado_civil')
                    ->searchable(),
                TextColumn::make('fecha_nacimiento')
                    ->date()
                    ->sortable(),
                TextColumn::make('telefono')
                    ->searchable(),
                TextColumn::make('direccion')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('apoderado.id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('grado_instruccion')
                    ->searchable(),
                TextColumn::make('provincia')
                    ->searchable(),
                TextColumn::make('distrito')
                    ->searchable(),
            ])
            ->filters([
                Filter::make('nro_documento')
                    ->label('DNI')
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $value): Builder => $query->where('nro_documento', 'like', "%{$value}%")
                        )
                    )
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['value']) {
                            return null;
                        }
                        return 'DNI: ' . $data['value'];
                    })
                    ->form([
                        \Filament\Forms\Components\TextInput::make('value')
                            ->label('DNI')
                            ->placeholder('Ingrese DNI'),
                    ]),

                Filter::make('nombres')
                    ->label('Nombre')
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $value): Builder => $query->where('nombres', 'like', "%{$value}%")
                        )
                    )
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['value']) {
                            return null;
                        }
                        return 'Nombre: ' . $data['value'];
                    })
                    ->form([
                        \Filament\Forms\Components\TextInput::make('value')
                            ->label('Nombre')
                            ->placeholder('Ingrese nombre'),
                    ]),

                Filter::make('apellido_paterno')
                    ->label('Apellido Paterno')
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $value): Builder => $query->where('apellido_paterno', 'like', "%{$value}%")
                        )
                    )
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['value']) {
                            return null;
                        }
                        return 'Apellido Paterno: ' . $data['value'];
                    })
                    ->form([
                        \Filament\Forms\Components\TextInput::make('value')
                            ->label('Apellido Paterno')
                            ->placeholder('Ingrese apellido paterno'),
                    ]),

                Filter::make('apellido_materno')
                    ->label('Apellido Materno')
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $value): Builder => $query->where('apellido_materno', 'like', "%{$value}%")
                        )
                    )
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['value']) {
                            return null;
                        }
                        return 'Apellido Materno: ' . $data['value'];
                    })
                    ->form([
                        \Filament\Forms\Components\TextInput::make('value')
                            ->label('Apellido Materno')
                            ->placeholder('Ingrese apellido materno'),
                    ]),
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
