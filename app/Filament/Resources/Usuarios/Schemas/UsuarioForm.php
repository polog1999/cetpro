<?php

namespace App\Filament\Resources\Usuarios\Schemas;

use App\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
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
                    ->relationship('empleado', 'nombre')
                    ->getOptionLabelFromRecordUsing(function (Empleado $e) {
                        return trim($e->nombre.' '.$e->apellido_paterno.' '.$e->apellido_materno);
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Seleccione el empleado para crear su usuario de acceso al sistema'),
                    
                Select::make('role_id')
                    ->label('Rol')
                    ->relationship('role', 'nombre')
                    ->getOptionLabelFromRecordUsing(function (Role $role) {
                        return $role->nombre . ($role->es_admin ? ' (Admin)' : '');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Seleccione el rol que determinará los permisos del usuario. Si selecciona "Profesor", se creará automáticamente un docente'),
                    
                TextInput::make('usuario')
                    ->label('Nombre de usuario')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Nombre de usuario único para iniciar sesión'),

                TextInput::make('password')
                    ->password()
                    ->label('Contraseña')
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn ($record) => $record === null)
                    ->helperText('Contraseña segura para acceso al sistema'),

                \Filament\Forms\Components\Toggle::make('activo')
                    ->label('Usuario Activo')
                    ->default(true)
                    ->helperText('Si desactiva, el usuario no podrá iniciar sesión'),
                

                // Section::make('Información del Usuario')
                //     ->description('Complete los datos para crear un nuevo usuario del sistema')
                //     ->schema([
                //     ]),
            ]);
    }
}