<?php

namespace App\Filament\Resources\Pagos;

use App\Filament\Resources\Secciones\Pages;
use App\Filament\Resources\Pagos\Pages\CreatePago;
use App\Filament\Resources\Pagos\Pages\EditPago;
use App\Filament\Resources\Pagos\Pages\ListPagos;
use App\Filament\Resources\Pagos\Pages\GestionarPagos;
use App\Filament\Resources\Pagos\Schemas\PagoForm;
use App\Filament\Resources\Pagos\Tables\PagosTable;
use App\Models\Pago;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PagoResource extends Resource
{
    protected static ?string $model = Pago::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $recordTitleAttribute = 'nombre';

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
            'index' => GestionarPagos::route('/'),
            'create' => CreatePago::route('/create'),
            'edit' => EditPago::route('/{record}/edit'),
        ];
    }
}
