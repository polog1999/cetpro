<?php

namespace App\Filament\Resources\Seccions;

use App\Filament\Resources\Seccions\Pages\CreateSeccion;
use App\Filament\Resources\Seccions\Pages\EditSeccion;
use App\Filament\Resources\Seccions\Pages\ListSeccions;
use App\Filament\Resources\Seccions\Schemas\SeccionForm;
use App\Filament\Resources\Seccions\Tables\SeccionsTable;
use App\Models\Seccion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Facades\Filament;
use App\Enums\Rol;

use UnitEnum;

class SeccionResource extends Resource
{
    protected static ?string $model = Seccion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | UnitEnum | null $navigationGroup = 'Gestión Académica';

    public static function form(Schema $schema): Schema
    {
        return SeccionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeccionsTable::configure($table);
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
            'index' => ListSeccions::route('/'),
            'create' => CreateSeccion::route('/create'),
            'edit' => EditSeccion::route('/{record}/edit'),
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
