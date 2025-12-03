<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Tables\RolesTable;
use App\Models\Role;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static string | UnitEnum | null $navigationGroup = 'Gestión de Usuarios';

    protected static ?string $navigationLabel = 'Roles';

    protected static ?string $pluralLabel = 'Roles';

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
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
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    // Solo los administradores pueden ver y gestionar roles
    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin ?? false;
    }

    public static function canCreate(): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin ?? false;
    }

    public static function canDelete($record): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
