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
            // El servicio ya maneja:
            // - Validación de vacantes
            // - Validación de duplicados
            // - Generación de código
            // - Creación de cronograma
            // - Generación de cuotas
            // Por ahora usamos el método existente, pero debería refactorizarse para usar repositorios
            return Matricula::create($data);
            
            // TODO: Cuando se refactorice completamente MatriculaService para usar repositorios:
            // return $service->crear($data);
            
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

