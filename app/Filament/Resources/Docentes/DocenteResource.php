<?php

namespace App\Filament\Resources\Docentes;

use App\Filament\Resources\Docentes\Pages\CreateDocente;
use App\Filament\Resources\Docentes\Pages\EditDocente;
use App\Filament\Resources\Docentes\Pages\ListDocentes;
use App\Filament\Resources\Docentes\Schemas\DocenteForm;
use App\Filament\Resources\Docentes\Tables\DocentesTable;
use App\Models\Docente;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Facades\Filament;
use App\Enums\Rol;

use UnitEnum;

class DocenteResource extends Resource
{
    protected static ?string $model = Docente::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | UnitEnum | null $navigationGroup = 'Gestión Académica';

    public static function form(Schema $schema): Schema
    {
        return DocenteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocentesTable::configure($table);
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
            'index' => ListDocentes::route('/'),
            'create' => CreateDocente::route('/create'),
            'edit' => EditDocente::route('/{record}/edit'),
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
