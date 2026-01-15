<?php

namespace App\Filament\Resources\Usuarios\Schemas;

use App\Models\Role;
use App\Models\Empleado;
use App\Models\Docente;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UsuarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // El campo empleado solo se muestra si NO es un usuario de alumno o docente
                Select::make('empleado_id')
                    ->label('Empleado')
                    ->relationship('empleado', 'nombre')
                    ->getOptionLabelFromRecordUsing(function (Empleado $e) {
                        return trim($e->nombre.' '.$e->apellido_paterno.' '.$e->apellido_materno);
                    })
                    ->searchable()
                    ->preload()
                    ->required(fn ($record) => $record === null || (!$record->estudiante_id && !$record->docente_id))
                    ->visible(fn ($record) => $record === null || (!$record->estudiante_id && !$record->docente_id))
                    ->helperText('Seleccione el empleado para crear su usuario de acceso al sistema'),
                    
                // Mostrar info del estudiante si es usuario de alumno (solo lectura)
                Placeholder::make('estudiante_info')
                    ->label('Estudiante')
                    ->content(fn ($record) => $record?->estudiante?->nombre_completo ?? '-')
                    ->visible(fn ($record) => $record?->estudiante_id),

                // Mostrar info del docente si es usuario de docente (solo lectura)
                Placeholder::make('docente_info')
                    ->label('Docente')
                    ->content(fn ($record) => $record?->docente?->nombre_completo ?? '-')
                    ->visible(fn ($record) => $record?->docente_id),
                    
                Select::make('role_id')
                    ->label('Rol')
                    ->relationship('role', 'nombre')
                    ->getOptionLabelFromRecordUsing(function (Role $role) {
                        return $role->nombre . ($role->es_admin ? ' (Admin)' : '');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn ($record) => $record?->estudiante_id || $record?->docente_id) // Deshabilitar para alumnos y docentes
                    ->helperText('Seleccione el rol que determinará los permisos del usuario'),
                    
                TextInput::make('usuario')
                    ->label('Nombre de usuario')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn ($record) => $record?->estudiante_id) // Deshabilitar solo para alumnos
                    ->helperText('Nombre de usuario único para iniciar sesión'),

                TextInput::make('password')
                    ->password()
                    ->label('Contraseña')
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn ($record) => $record === null)
                    ->helperText('Contraseña segura para acceso al sistema'),

                Toggle::make('activo')
                    ->label('Usuario Activo')
                    ->default(true)
                    ->helperText('Si desactiva, el usuario no podrá iniciar sesión'),
            ]);
    }
}