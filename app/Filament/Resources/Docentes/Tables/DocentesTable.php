<?php

namespace App\Filament\Resources\Docentes\Tables;

use App\Filament\Traits\PreventDeleteWithDependencies;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

class DocentesTable
{
    use PreventDeleteWithDependencies;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nro_documento')
                    ->label('N° Documento')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombres')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('apellido_paterno')
                    ->label('Apellido Paterno')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('apellido_materno')
                    ->label('Apellido Materno')
                    ->searchable(),
                    
                // Columna visual para horarios
                TextColumn::make('horarios_count')
                    ->label('Horarios')
                    ->counts('horarios')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                // Indicador de usuario
                IconColumn::make('usuario')
                    ->label('Usuario')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->usuario()->exists())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
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
                DeleteAction::make()
                    ->before(function (DeleteAction $action, $record) {
                        // No permitir eliminar si tiene horarios asignados
                        if ($record->horarios()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('No se puede eliminar')
                                ->body('El docente tiene horarios asignados.')
                                ->send();
                            $action->cancel();
                        }
                        
                        // Eliminar el usuario asociado si existe
                        if ($record->usuario) {
                            $record->usuario->delete();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(fn (DeleteBulkAction $action, $records) => 
                            self::preventBulkDeleteWithDependencies(
                                $action,
                                $records,
                                'horarios',
                                'horario(s)',
                                'nombres'
                            )
                        ),
                ]),
            ]);
    }
}
