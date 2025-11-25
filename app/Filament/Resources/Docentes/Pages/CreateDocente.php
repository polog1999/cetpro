<?php

namespace App\Filament\Resources\Docentes\Pages;

use App\Filament\Resources\Docentes\DocenteResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Docente;
use Filament\Notifications\Notification;

class CreateDocente extends CreateRecord
{
    protected static string $resource = DocenteResource::class;

    protected function beforeCreate(): void
    {
        // Verificar si existe un docente idéntico
        $exists = Docente::where('tipo_documento', $this->data['tipo_documento'])
            ->where('nro_documento', $this->data['nro_documento'])
            ->where('nombres', $this->data['nombres'])
            ->where('apellido_paterno', $this->data['apellido_paterno'])
            ->where('apellido_materno', $this->data['apellido_materno'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Error al crear nuevo Docente')
                ->body('No se admiten registros idénticos.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
