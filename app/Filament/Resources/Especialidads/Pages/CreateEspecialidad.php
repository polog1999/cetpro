<?php

namespace App\Filament\Resources\Especialidads\Pages;

use App\Filament\Resources\Especialidads\EspecialidadResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Especialidad;
use Filament\Notifications\Notification;

class CreateEspecialidad extends CreateRecord
{
    protected static string $resource = EspecialidadResource::class;

    protected function beforeCreate(): void
    {
        // Verificar si existe una especialidad idéntica
        $exists = Especialidad::where('nombre_especialidad', $this->data['nombre_especialidad'])
            ->where('costo_mensual', $this->data['costo_mensual'])
            ->where('num_resolucion', $this->data['num_resolucion'])
            ->where('fecha_registro', $this->data['fecha_registro'])
            ->where('fecha_inicio_vigencia', $this->data['fecha_inicio_vigencia'])
            ->where('fecha_fin_vigencia', $this->data['fecha_fin_vigencia'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Error al crear nuevo Especialidad')
                ->body('No se admiten registros idénticos.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
