<?php

namespace App\Filament\Resources\Pagos;

use App\Filament\Resources\Pagos\Pages\CreatePago;
use App\Filament\Resources\Pagos\Pages\EditPago;
use App\Filament\Resources\Pagos\Pages\ListPagos;
use App\Filament\Resources\Pagos\Pages\ReasignarPagos;
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

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
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
            'reasignar' => ReasignarPagos::route('/reasignar'),
        ];
    }

    //Accesos
    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        return $user?->esAdmin() || $user?->canAccessResource('pagos') || false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
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
    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::count();
    // }

    /**
     * Habilita la búsqueda global para Pagos.
     * Permite buscar por código del pago.
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['num_liquidacion', 'estado'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return 'Pago: ' . ($record->num_liquidacion ?? 'Cuota #' . $record->nro_cuota);
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Monto' => 'S/. ' . number_format($record->monto, 2),
            'Estado' => $record->estado?->getLabel() ?? 'Sin estado',
        ];
    }
}
