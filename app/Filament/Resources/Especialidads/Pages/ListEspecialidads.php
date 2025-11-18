<?php

namespace App\Filament\Resources\Especialidads\Pages;

use App\Filament\Resources\Especialidads\EspecialidadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEspecialidads extends ListRecords
{
    protected static string $resource = EspecialidadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
