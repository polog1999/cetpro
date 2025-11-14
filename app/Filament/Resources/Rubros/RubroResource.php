<?php

namespace App\Filament\Resources\Rubros;

use App\Filament\Resources\Rubros\Pages\CreateRubro;
use App\Filament\Resources\Rubros\Pages\EditRubro;
use App\Filament\Resources\Rubros\Pages\ListRubros;
use App\Filament\Resources\Rubros\Schemas\RubroForm;
use App\Filament\Resources\Rubros\Tables\RubrosTable;
use App\Models\Rubro;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Facades\Filament;
use App\Enums\Rol;

use UnitEnum;

class RubroResource extends Resource
{
    protected static ?string $model = Rubro::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static string | UnitEnum | null $navigationGroup = 'Gestión Académica';

    public static function form(Schema $schema): Schema
    {
        return RubroForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RubrosTable::configure($table);
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
            'index' => ListRubros::route('/'),
            'create' => CreateRubro::route('/create'),
            'edit' => EditRubro::route('/{record}/edit'),
        ];
    }

    //Accesos
    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        // Si guardas el rol como enum:
        return $user?->rol === Rol::ADMIN;

        // Si guardas el rol como string:
        // return $user?->rol === 'admin';
    }

    public static function canCreate(): bool
    {
        $user = Filament::auth()->user();
        return $user?->rol === Rol::ADMIN; // o 'admin'
    }

    public static function canEdit($record): bool
    {
        $user = Filament::auth()->user();
        return $user?->rol === Rol::ADMIN; // o 'admin'
    }

    public static function canDelete($record): bool
    {
        $user = Filament::auth()->user();
        return $user?->rol === Rol::ADMIN; // o 'admin'
    }

    public static function canDeleteAny(): bool
    {
        $user = Filament::auth()->user();
        return $user?->rol === Rol::ADMIN; // o 'admin'
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Filament::auth()->user();
        return $user?->rol === Rol::ADMIN; // o 'admin'
    }

    //Contar
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
