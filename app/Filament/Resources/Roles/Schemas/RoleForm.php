<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Permiso;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Información básica del rol
                TextInput::make('nombre')
                    ->label('Nombre del Rol')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->placeholder('Ej: Contador, Coordinador, etc.')
                    ->columnSpanFull(),

                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->rows(2)
                    ->placeholder('Descripción breve del rol y sus responsabilidades')
                    ->columnSpanFull(),

                Toggle::make('es_admin')
                    ->label('¿Es Administrador?')
                    ->helperText('Los administradores tienen acceso total al sistema')
                    ->live()
                    ->columnSpanFull(),

                // Permisos por grupo
                CheckboxList::make('permisos_estudiantil')
                    ->label('Gestión Estudiantil')
                    ->options(function () {
                        return Permiso::where('grupo', 'Gestión Estudiantil')
                            ->pluck('nombre', 'id')
                            ->toArray();
                    })
                    ->columns(2)
                    ->gridDirection('row')
                    ->disabled(fn (Get $get) => $get('es_admin'))
                    ->hidden(fn (Get $get) => $get('es_admin'))
                    ->columnSpanFull(),

                CheckboxList::make('permisos_academica')
                    ->label('Gestión Académica')
                    ->options(function () {
                        return Permiso::where('grupo', 'Gestión Académica')
                            ->pluck('nombre', 'id')
                            ->toArray();
                    })
                    ->columns(2)
                    ->gridDirection('row')
                    ->disabled(fn (Get $get) => $get('es_admin'))
                    ->hidden(fn (Get $get) => $get('es_admin'))
                    ->columnSpanFull(),

                CheckboxList::make('permisos_administrativa')
                    ->label('Gestión Administrativa')
                    ->options(function () {
                        return Permiso::where('grupo', 'Gestión Administrativa')
                            ->pluck('nombre', 'id')
                            ->toArray();
                    })
                    ->columns(2)
                    ->gridDirection('row')
                    ->disabled(fn (Get $get) => $get('es_admin'))
                    ->hidden(fn (Get $get) => $get('es_admin'))
                    ->columnSpanFull(),

                CheckboxList::make('permisos_financiera')
                    ->label('Gestión Financiera')
                    ->options(function () {
                        return Permiso::where('grupo', 'Gestión Financiera')
                            ->pluck('nombre', 'id')
                            ->toArray();
                    })
                    ->columns(2)
                    ->gridDirection('row')
                    ->disabled(fn (Get $get) => $get('es_admin'))
                    ->hidden(fn (Get $get) => $get('es_admin'))
                    ->columnSpanFull(),

                CheckboxList::make('permisos_usuarios')
                    ->label('Gestión de Usuarios')
                    ->options(function () {
                        return Permiso::where('grupo', 'Gestión de Usuarios')
                            ->pluck('nombre', 'id')
                            ->toArray();
                    })
                    ->columns(2)
                    ->gridDirection('row')
                    ->disabled(fn (Get $get) => $get('es_admin'))
                    ->hidden(fn (Get $get) => $get('es_admin'))
                    ->columnSpanFull(),
            ]);
    }
}
