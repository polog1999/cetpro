<?php

namespace App\Filament\Resources\Especialidads;

use App\Filament\Resources\Especialidads\Pages\CreateEspecialidad;
use App\Filament\Resources\Especialidads\Pages\EditEspecialidad;
use App\Filament\Resources\Especialidads\Pages\ListEspecialidads;
use App\Filament\Resources\Especialidads\Schemas\EspecialidadForm;
use App\Filament\Resources\Especialidads\Tables\EspecialidadsTable;
use App\Models\Especialidad;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Facades\Filament;
use App\Enums\Rol;

use UnitEnum;

class EspecialidadResource extends Resource
{
    protected static ?string $model = Especialidad::class;
    protected static ?string $navigationLabel = 'Especialidades';
    protected static ?string $pluralModelLabel = 'Especialidades';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | UnitEnum | null $navigationGroup = 'Gestión Académica';

    public static function form(Schema $schema): Schema
    {
        return EspecialidadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EspecialidadsTable::configure($table);
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
            'index' => ListEspecialidads::route('/'),
            'create' => CreateEspecialidad::route('/create'),
            'edit' => EditEspecialidad::route('/{record}/edit'),
        ];
    }

    //Accesos
    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        return $user?->role?->es_admin || $user?->canAccessResource('EspecialidadResource') || false;
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
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }


//Contar
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
