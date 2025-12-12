<?php

namespace App\Filament\Resources\Apoderados;

use App\Filament\Resources\Apoderados\Pages\CreateApoderado;
use App\Filament\Resources\Apoderados\Pages\EditApoderado;
use App\Filament\Resources\Apoderados\Pages\ListApoderados;
use App\Filament\Resources\Apoderados\Schemas\ApoderadoForm;
use App\Filament\Resources\Apoderados\Tables\ApoderadosTable;
use App\Models\Apoderado;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use UnitEnum;

class ApoderadoResource extends Resource
{
    protected static string | UnitEnum | null $navigationGroup = 'Gestión de Matrícula';

    protected static ?string $model = Apoderado::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nombres';

    public static function form(Schema $schema): Schema
    {
        return ApoderadoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApoderadosTable::configure($table);
    }

        public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
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
            'index' => ListApoderados::route('/'),
            'create' => CreateApoderado::route('/create'),
            'edit' => EditApoderado::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = \Filament\Facades\Filament::auth()->user();
        return $user?->role?->es_admin || $user?->canAccessResource('apoderados') || false;
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

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
