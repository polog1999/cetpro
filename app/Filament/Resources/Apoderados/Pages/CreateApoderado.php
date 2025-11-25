<?php

namespace App\Filament\Resources\Apoderados\Pages;

use App\Filament\Resources\Apoderados\ApoderadoResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Apoderado;
use Filament\Notifications\Notification;

class CreateApoderado extends CreateRecord
{
    protected static string $resource = ApoderadoResource::class;

    protected function beforeCreate(): void
    {
        // Verificar si existe un apoderado idéntico
        $exists = Apoderado::where('tipo_documento', $this->data['tipo_documento'])
            ->where('nro_documento', $this->data['nro_documento'])
            ->where('nombres', $this->data['nombres'])
            ->where('apellido_paterno', $this->data['apellido_paterno'])
            ->where('apellido_materno', $this->data['apellido_materno'])
            ->where('telefono', $this->data['telefono'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Error al crear nuevo Apoderado')
                ->body('No se admiten registros idénticos.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
