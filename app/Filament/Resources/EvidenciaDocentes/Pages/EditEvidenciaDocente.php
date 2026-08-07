<?php

namespace App\Filament\Resources\EvidenciaDocentes\Pages;

use App\Filament\Resources\EvidenciaDocentes\EvidenciaDocenteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvidenciaDocente extends EditRecord
{
    protected static string $resource = EvidenciaDocenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
