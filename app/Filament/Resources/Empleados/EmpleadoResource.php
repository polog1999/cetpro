<?php

namespace App\Filament\Resources\Empleados;

use App\Filament\Resources\Empleados\Pages\CreateEmpleado;
use App\Filament\Resources\Empleados\Pages\EditEmpleado;
use App\Filament\Resources\Empleados\Pages\ListEmpleados;
use App\Filament\Resources\Empleados\Schemas\EmpleadoForm;
use App\Filament\Resources\Empleados\Tables\EmpleadosTable;
use App\Models\Empleado;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use App\Enums\Rol;

class EmpleadoResource extends Resource
{
    protected static ?string $model = Empleado::class;
    
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string | UnitEnum | null $navigationGroup = 'Gestión de Usuarios';

    public static function form(Schema $schema): Schema
    {
        return EmpleadoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmpleadosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmpleados::route('/'),
            'create' => CreateEmpleado::route('/create'),
            'edit' => EditEmpleado::route('/{record}/edit'),
        ];
    }


    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        // Empleados solo visible para admin o quien tenga permiso de empleados
        return $user?->role?->es_admin || $user?->canAccessResource('empleados') || false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        if (!static::canViewAny()) {
            return false;
        }
        
        // No permitir eliminación si el empleado tiene usuario asignado
        return !$record->usuario()->exists();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::count();
    // }
}
