<?php

namespace App\Filament\Resources\EvidenciaDocentes\Pages;

use App\Filament\Resources\EvidenciaDocentes\EvidenciaDocenteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEvidenciaDocentes extends ListRecords
{
    protected static string $resource = EvidenciaDocenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
