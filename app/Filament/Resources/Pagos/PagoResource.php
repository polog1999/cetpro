<?php

namespace App\Filament\Resources\Pagos;

use App\Filament\Resources\Pagos\Pages\CreatePago;
use App\Filament\Resources\Pagos\Pages\EditPago;
use App\Filament\Resources\Pagos\Pages\ListPagos;
use App\Filament\Resources\Pagos\Schemas\PagoForm;
use App\Filament\Resources\Pagos\Tables\PagosTable;
use App\Models\Pago;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Facades\Filament;
use App\Enums\Rol;

use UnitEnum;

class PagoResource extends Resource
{
    protected static ?string $model = Pago::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string | UnitEnum | null $navigationGroup = 'Gestión de Pagos';

    public static function form(Schema $schema): Schema
    {
        return PagoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagosTable::configure($table);
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
            'index' => ListPagos::route('/'),
            'create' => CreatePago::route('/create'),
            'edit' => EditPago::route('/{record}/edit'),
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
