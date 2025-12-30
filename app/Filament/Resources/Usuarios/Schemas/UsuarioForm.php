<?php

namespace App\Filament\Resources\Usuarios\Schemas;

use App\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use App\Models\Empleado;

class UsuarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('empleado_id')
                    ->label('Empleado')
                    ->relationship('empleado', 'nombre') // base para la relación
                    ->getOptionLabelFromRecordUsing(function (Empleado $e) {
                        return trim($e->nombre.' '.$e->apellido_paterno.' '.$e->apellido_materno);
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Seleccione el empleado para crear su usuario de acceso'),
                    
                Select::make('role_id')
                    ->label('Rol')
                    ->relationship('role', 'nombre')
                    ->getOptionLabelFromRecordUsing(function (Role $role) {
                        return $role->nombre . ($role->es_admin ? ' (Admin)' : '');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Seleccione el rol que determinará los permisos del usuario'),
                    
                TextInput::make('usuario')
                    ->label('Nombre de usuario')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Nombre de usuario para iniciar sesión'),

                TextInput::make('password')
                    ->password()
                    ->label('Contraseña')
                    ->dehydrated(fn ($state) => filled($state)) // no sobrescribe si está vacío en editar
                    ->required(fn ($record) => $record === null)
                    ->helperText('Contraseña de acceso al sistema'),

                \Filament\Forms\Components\Toggle::make('activo')
                    ->label('Usuario Activo')
                    ->default(true)
                    ->helperText('Si se desactiva, el usuario no podrá iniciar sesión.'),
            ]);
    }
}