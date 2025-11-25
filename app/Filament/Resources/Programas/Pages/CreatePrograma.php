<?php

namespace App\Filament\Resources\Programas\Pages;

use App\Filament\Resources\Programas\ProgramaResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Programa;
use Filament\Notifications\Notification;

class CreatePrograma extends CreateRecord
{
    protected static string $resource = ProgramaResource::class;

    protected function beforeCreate(): void
    {
        // Verificar si existe un programa idéntico
        $exists = Programa::where('nombre_programa', $this->data['nombre_programa'])
            ->where('duracion', $this->data['duracion'])
            ->where('num_cursos', $this->data['num_cursos'])
            ->where('id_especialidad', $this->data['id_especialidad'])
            ->where('tipo_programa', $this->data['tipo_programa'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Error al crear nuevo Programa')
                ->body('No se admiten registros idénticos.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
