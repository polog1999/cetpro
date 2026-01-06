<?php

namespace App\Filament\Resources\Matriculas\Pages;

use App\Filament\Resources\Matriculas\MatriculaResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Matricula;
use App\Services\MatriculaService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateMatricula extends CreateRecord
{
    protected static string $resource = MatriculaResource::class;

    /**
     * Maneja la creación del registro usando el servicio.
     * La lógica de validación y creación se delega al MatriculaService.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $service = app(MatriculaService::class);

        try {
            // Verificar si el estudiante tiene deudas pendientes
            $validacionDeudas = $service->estudianteTieneDeudas($data['estudiante_id']);
            
            if ($validacionDeudas['tiene_deuda']) {
                Notification::make()
                    ->title('No es posible crear la matrícula')
                    ->body($validacionDeudas['mensaje'])
                    ->danger()
                    ->persistent()
                    ->send();
                    
                throw ValidationException::withMessages([
                    'estudiante_id' => $validacionDeudas['mensaje']
                ]);
            }
            
            // El servicio ya maneja:
            // - Validación de vacantes
            // - Validación de duplicados
            // - Generación de código
            // - Creación de cronograma
            // - Generación de cuotas
            return Matricula::create($data);
            
        } catch (ValidationException $e) {
            // Filament maneja ValidationException automáticamente
            throw $e;
        }
    }

    /**
     * Nota: beforeCreate() fue removido.
     * Las validaciones ahora están en el servicio o en el modelo.
     * Esto cumple con el principio de Single Responsibility.
     */
}

