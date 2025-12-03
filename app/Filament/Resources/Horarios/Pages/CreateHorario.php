<?php

namespace App\Filament\Resources\Horarios\Pages;

use App\Filament\Resources\Horarios\HorarioResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHorario extends CreateRecord
{
    protected static string $resource = HorarioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $inicio = $data['hora_inicio'] ?? null;
        $fin    = $data['hora_fin'] ?? null;

        if ($inicio && $fin) {
            $data['horario'] = "{$inicio} - {$fin}";
        } else {
            $data['horario'] = null;
        }

        // No existen columnas hora_inicio / hora_fin en la BD,
        // así que las quitamos para evitar errores.
        unset($data['hora_inicio'], $data['hora_fin']);

        return $data;
    }
}
