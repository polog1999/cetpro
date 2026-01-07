<?php

namespace App\Filament\Resources\Matriculas\Pages;

use App\Filament\Resources\Matriculas\MatriculaResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Matricula;
use App\Services\MatriculaService;
use App\Services\OracleTusneService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

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
     * Después de crear la matrícula, verificar si el estudiante tiene código de contribuyente.
     * Si no lo tiene, crearlo en Oracle.
     */
    protected function afterCreate(): void
    {
        $estudiante = $this->record->estudiante;
        
        // Si el estudiante ya tiene código, no hacer nada
        if (!empty($estudiante->codigo_contribuyente)) {
            return;
        }
        
        // Crear contribuyente en Oracle
        try {
            $oracle = app(OracleTusneService::class);
            $codigo = $oracle->crearContribuyente($estudiante);
            
            if ($codigo) {
                $estudiante->codigo_contribuyente = $codigo;
                $estudiante->save();
                
                Notification::make()
                    ->success()
                    ->title('Contribuyente creado')
                    ->body("Código: {$codigo}")
                    ->send();
                    
                Log::info('Contribuyente creado al matricular', [
                    'estudiante_id' => $estudiante->id,
                    'matricula_id' => $this->record->id,
                    'codigo' => $codigo,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al crear contribuyente al matricular', [
                'estudiante_id' => $estudiante->id,
                'matricula_id' => $this->record->id,
                'error' => $e->getMessage(),
            ]);
            
            Notification::make()
                ->warning()
                ->title('Matrícula creada sin código contribuyente')
                ->body('No se pudo conectar con Oracle')
                ->send();
        }
    }
}
