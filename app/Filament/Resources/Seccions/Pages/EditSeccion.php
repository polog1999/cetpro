<?php

namespace App\Filament\Resources\Seccions\Pages;

use App\Filament\Resources\Seccions\SeccionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSeccion extends EditRecord
{
    protected static string $resource = SeccionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // Al abrir el formulario, llenar hora_inicio y hora_fin según el horario guardado
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! empty($data['horario']) && str_contains($data['horario'], ' - ')) {
            [$inicio, $fin] = explode(' - ', $data['horario']);
            $data['hora_inicio'] = $inicio;
            $data['hora_fin']    = $fin;
        }

        return $data;
    }

    // Antes de guardar, volver a construir horario y limpiar las horas
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $inicio = $data['hora_inicio'] ?? null;
        $fin    = $data['hora_fin'] ?? null;

        if ($inicio && $fin) {
            $data['horario'] = "{$inicio} - {$fin}";
        }

        unset($data['hora_inicio'], $data['hora_fin']);

        return $data;
    }
}
