<?php

namespace App\Filament\Resources\Notas\Tables;

use App\Enums\CalificacionLetra;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('matricula.codigo_inscripcion')
                    ->label('Código de matrícula')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('matricula.estudiante.nombre_completo')
                    ->label('Estudiante')
                    ->searchable(['matricula.estudiante.nombres', 'matricula.estudiante.apellido_paterno'])
                    ->sortable(),
                TextColumn::make('curso.nombre_curso')
                    ->label('Curso')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('docente.nombres')
                    ->label('Docente')
                    ->sortable(),
                TextColumn::make('nota_numerica')
                    ->label('Nota numérica')
                    ->badge()
                    ->color(fn ($state) => $state !== null && $state >= 11 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('nota_letra')
                    ->label('Nota letra')
                    ->badge()
                    ->color(fn ($state) => in_array($state?->value ?? $state, ['AD', 'A']) ? 'success' : 'warning'),
                TextColumn::make('created_at')
                    ->label('Fecha registro')
                    ->dateTime('d/m/Y H:i')
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
            ->recordActions([
                // Acción de rectificación - solo para directores (no profesores)
                \Filament\Actions\Action::make('rectificar')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn () => !auth()->user()?->esProfesor())
                    ->modalHeading('Rectificar Nota')
                    ->modalDescription('Ingrese los nuevos valores para la rectificación de la nota.')
                    ->modalSubmitActionLabel('Guardar rectificación')
                    ->form(function ($record) {
                        // Determinar si la nota original es numérica o en letras
                        $esNotaNumerica = $record->nota_numerica !== null;
                        $esNotaLetra = $record->nota_letra !== null;
                        
                        $campos = [];
                        
                        if ($esNotaNumerica) {
                            $campos[] = TextInput::make('nota_numerica')
                                ->label('Nota numérica')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(20)
                                ->step(0.01)
                                ->required()
                                ->default($record->nota_numerica)
                                ->helperText('Ingrese una nota entre 0 y 20');
                        }
                        
                        if ($esNotaLetra) {
                            $campos[] = Select::make('nota_letra')
                                ->label('Nota en letras')
                                ->options(CalificacionLetra::class)
                                ->required()
                                ->default($record->nota_letra)
                                ->helperText('Seleccione la calificación en letra');
                        }
                        
                        // Si no tiene ninguna nota, mostrar ambas opciones
                        if (!$esNotaNumerica && !$esNotaLetra) {
                            $campos[] = TextInput::make('nota_numerica')
                                ->label('Nota numérica')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(20)
                                ->step(0.01)
                                ->default($record->nota_numerica);
                                
                            $campos[] = Select::make('nota_letra')
                                ->label('Nota en letras')
                                ->options(CalificacionLetra::class)
                                ->default($record->nota_letra);
                        }
                        
                        return $campos;
                    })
                    ->action(function ($record, array $data) {
                        // Actualizar solo los campos de nota
                        $updateData = [];
                        
                        if (array_key_exists('nota_numerica', $data)) {
                            $updateData['nota_numerica'] = $data['nota_numerica'];
                        }
                        
                        if (array_key_exists('nota_letra', $data)) {
                            $updateData['nota_letra'] = $data['nota_letra'];
                        }
                        
                        $record->update($updateData);
                        
                        Notification::make()
                            ->success()
                            ->title('Nota rectificada')
                            ->body('La nota ha sido actualizada correctamente.')
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => !auth()->user()?->esProfesor()),
                ]),
            ]);
    }
}
