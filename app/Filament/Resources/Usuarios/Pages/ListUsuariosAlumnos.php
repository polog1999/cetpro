<?php

namespace App\Filament\Resources\Usuarios\Pages;

use App\Models\Usuario;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Usuarios\UsuarioResource;

class ListUsuariosAlumnos extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = UsuarioResource::class;
    
    protected string $view = 'filament.resources.usuarios.pages.list-usuarios-alumnos';
    
    protected static ?string $title = 'Usuarios de Alumnos';
    
    protected static ?string $navigationLabel = 'Usuarios Alumnos';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(Usuario::query()->whereNotNull('estudiante_id'))
            ->columns([
                TextColumn::make('estudiante.nro_documento')
                    ->label('DNI')
                    ->searchable()
                    ->copyable(),
                    
                TextColumn::make('estudiante.nombre_completo')
                    ->label('Estudiante')
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('estudiante', function ($q) use ($search) {
                            $q->where('nombres', 'ilike', "%{$search}%")
                              ->orWhere('apellido_paterno', 'ilike', "%{$search}%")
                              ->orWhere('apellido_materno', 'ilike', "%{$search}%");
                        });
                    }),
                    
                TextColumn::make('usuario')
                    ->label('Usuario')
                    ->searchable(),
                    
                TextColumn::make('activo')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                    
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Cambiar contraseña (solo admin)
                \Filament\Actions\Action::make('cambiar_password')
                    ->label('')
                    ->tooltip('Cambiar Contraseña')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->role?->es_admin)
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
                    ->modalHeading(fn ($record) => "Cambiar contraseña de {$record->estudiante->nombre_completo}")
                    ->action(function ($record, array $data) {
                        $record->update([
                            'password' => $data['nueva_password'],
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Contraseña actualizada')
                            ->success()
                            ->send();
                    }),
                    
                // Activar/Desactivar
                \Filament\Actions\Action::make('toggle_activo')
                    ->label('')
                    ->tooltip(fn ($record) => $record->activo ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->activo ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->activo ? 'danger' : 'success')
                    ->visible(fn () => auth()->user()?->role?->es_admin)
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->activo ? 'Desactivar usuario' : 'Activar usuario')
                    ->modalDescription(fn ($record) => $record->activo 
                        ? "¿Desea desactivar el acceso de {$record->estudiante->nombre_completo}?" 
                        : "¿Desea activar el acceso de {$record->estudiante->nombre_completo}?")
                    ->action(function ($record) {
                        $record->update(['activo' => !$record->activo]);
                        \Filament\Notifications\Notification::make()
                            ->title($record->activo ? 'Usuario activado' : 'Usuario desactivado')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('Volver a Usuarios')
                ->icon('heroicon-o-arrow-left')
                ->url(UsuarioResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
