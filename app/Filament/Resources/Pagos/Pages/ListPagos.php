<?php

namespace App\Filament\Resources\Pagos\Pages;

use App\Filament\Resources\Pagos\PagoResource;
use Filament\Resources\Pages\ListRecords;

class ListPagos extends ListRecords
{
    protected static string $resource = PagoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Botón de crear deshabilitado porque los pagos se generan automáticamente
        ];
    }
}
