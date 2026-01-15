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
            // Solo mostrar usuarios de personal (no alumnos)
            ->modifyQueryUsing(fn ($query) => $query->whereNull('estudiante_id'))
            ->columns([
                TextColumn::make('persona')
                    ->label('Empleado / Docente')
                    ->getStateUsing(function ($record) {
                        // Primero verificar si tiene empleado
                        if ($record->empleado) {
                            $emp = $record->empleado;
                            return "{$emp->nombre} {$emp->apellido_paterno} {$emp->apellido_materno}";
                        }
                        // Si no tiene empleado, verificar si tiene docente
                        if ($record->docente) {
                            $doc = $record->docente;
                            return "{$doc->nombres} {$doc->apellido_paterno} {$doc->apellido_materno}";
                        }
                        return '-';
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->whereHas('empleado', function ($subQ) use ($search) {
                                $subQ->where('nombre', 'ilike', "%{$search}%")
                                    ->orWhere('apellido_paterno', 'ilike', "%{$search}%")
                                    ->orWhere('apellido_materno', 'ilike', "%{$search}%");
                            })
                            ->orWhereHas('docente', function ($subQ) use ($search) {
                                $subQ->where('nombres', 'ilike', "%{$search}%")
                                    ->orWhere('apellido_paterno', 'ilike', "%{$search}%")
                                    ->orWhere('apellido_materno', 'ilike', "%{$search}%");
                            });
                        });
                    }),
                    
                TextColumn::make('usuario')
                    ->searchable(),
                    
                TextColumn::make('role.nombre')
                    ->label('Rol')
                    ->searchable()
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Administrador' => 'success',
                        'Admin' => 'success',
                        'Director' => 'warning',
                        'Secretaria' => 'info',
                        'Profesor' => 'primary',
                        default => 'gray',
                    }),
                    
                TextColumn::make('activo')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                    
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
