<?php

namespace App\Filament\Resources\Estudiantes\Tables;

use App\Filament\Traits\PreventDeleteWithDependencies;
use App\Services\OracleTusneService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Filament\Tables\Enums\RecordActionsPosition;

class EstudiantesTable
{
    use PreventDeleteWithDependencies;
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nro_documento')
                    ->searchable(),
                
                TextColumn::make('codigo_contribuyente')
                    ->label('Cód. Contrib.')
                    ->getStateUsing(function ($record): string {
                        return !empty($record->codigo_contribuyente) 
                            ? $record->codigo_contribuyente 
                            : 'Sin código';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sin código' => 'gray',
                        default => 'success',
                    })
                    ->copyable()
                    ->copyMessage('Código copiado'),
                    
                TextColumn::make('nombres')
                    ->searchable(),
                TextColumn::make('apellido_paterno')
                    ->searchable(),
                TextColumn::make('apellido_materno')
                    ->searchable(),
                TextColumn::make('genero')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('estado_civil')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fecha_nacimiento')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('telefono')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('direccion')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                // Columna de matrículas
                TextColumn::make('matriculas_count')
                    ->label('Matrículas')
                    ->counts('matriculas')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                    
                // Columna de usuario de portal
                TextColumn::make('usuario.usuario')
                    ->label('Usuario Portal')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->default('Sin usuario')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('apoderado.nombre_completo'),
                TextColumn::make('grado_instruccion')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('provincia')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('distrito')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                \Filament\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Ver')
                    ->visible(fn () => !auth()->user()?->esProfesor()),
                EditAction::make()
                    ->label('')
                    ->tooltip('Editar')
                    ->visible(fn () => !auth()->user()?->esProfesor()),
                
                \Filament\Actions\Action::make('ver_pagos')
                    ->label('')
                    ->tooltip('Ver pagos')
                    ->visible(fn () => !auth()->user()?->esProfesor())
                    ->icon('heroicon-o-currency-dollar')
                    ->color('info')
                    ->modalHeading(fn($record) => "Pagos de {$record->nombre_completo}")
                    ->modalWidth('5xl')
                    ->modalContent(fn($record) => view('filament.estudiantes.ver-pagos-modal', [
                        'estudiante' => $record->load([
                            'matriculas.horario.programa',
                            'matriculas.curso',
                            'matriculas.cronograma.pagos' => function($query) {
                                $query->orderBy('nro_cuota');
                            }
                        ])
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                
                // Crear usuario de alumno
                \Filament\Actions\Action::make('crear_usuario')
                    ->label('')
                    ->tooltip('Crear usuario de portal')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn ($record) => !auth()->user()?->esProfesor() && !$record->usuario)
                    ->requiresConfirmation()
                    ->modalHeading('Crear usuario de portal')
                    ->modalDescription(fn ($record) => "Se creará un usuario para {$record->nombre_completo} con DNI como usuario y contraseña.")
                    ->action(function ($record) {
                        try {
                            $service = app(\App\Services\EstudianteService::class);
                            $service->crearUsuarioParaEstudiante($record);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Usuario creado')
                                ->body("Usuario: {$record->nro_documento} / Contraseña: {$record->nro_documento}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                // Cambiar contraseña de alumno (solo admin)
                \Filament\Actions\Action::make('cambiar_password')
                    ->label('')
                    ->tooltip('Cambiar contraseña')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn ($record) => auth()->user()?->role?->es_admin && $record->usuario)
                    ->form([
                        \Filament\Forms\Components\TextInput::make('nueva_password')
                            ->label('Nueva Contraseña')
                            ->password()
                            ->required()
                            ->minLength(4)
                            ->helperText('Mínimo 4 caracteres'),
                        \Filament\Forms\Components\TextInput::make('confirmar_password')
                            ->label('Confirmar Contraseña')
                            ->password()
                            ->required()
                            ->same('nueva_password'),
                    ])
                    ->modalHeading(fn ($record) => "Cambiar contraseña de {$record->nombre_completo}")
                    ->action(function ($record, array $data) {
                        $record->usuario->update([
                            'password' => $data['nueva_password'],
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Contraseña actualizada')
                            ->success()
                            ->send();
                    }),
                
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Eliminar')
                    ->visible(fn () => !auth()->user()?->esProfesor())
                    ->before(fn (DeleteAction $action, $record) => 
                        self::preventDeleteWithDependencies(
                            $action,
                            $record,
                            'matriculas',
                            'matrícula(s)'
                        )
                    ),
                ], position: RecordActionsPosition::BeforeCells)
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(fn (DeleteBulkAction $action, $records) => 
                            self::preventBulkDeleteWithDependencies(
                                $action,
                                $records,
                                'matriculas',
                                'matrícula(s)',
                                'nombres'
                            )
                        ),
                        
                    // Acción masiva para crear usuarios
                    \Filament\Actions\BulkAction::make('crear_usuarios_masivo')
                        ->label('Crear usuarios de portal')
                        ->icon('heroicon-o-user-plus')
                        ->requiresConfirmation()
                        ->modalHeading('Crear usuarios para seleccionados')
                        ->modalDescription('Se crearán cuentas de usuario (DNI/DNI) para los estudiantes seleccionados que aún no tengan cuenta.')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = 0;
                            $service = app(\App\Services\EstudianteService::class);
                            
                            foreach ($records as $record) {
                                if (!$record->usuario) {
                                    try {
                                        $service->crearUsuarioParaEstudiante($record);
                                        $count++;
                                    } catch (\Exception $e) {
                                        // Continuar con el siguiente
                                    }
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title("Se crearon {$count} usuarios")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
