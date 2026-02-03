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
     * Si no lo tiene, crearlo en Oracle y luego regenerar las liquidaciones de los pagos.
     */
    protected function afterCreate(): void
    {
        $estudiante = $this->record->estudiante;
        $oracle = app(OracleTusneService::class);
        $codigoContribuyente = null;
        
        // 1. VERIFICACIÓN LOCAL PRIMERO - Si ya tiene código guardado, usarlo directamente
        if (!empty($estudiante->codigo_contribuyente)) {
            $codigoContribuyente = $estudiante->codigo_contribuyente;
            Log::info('Usando código de contribuyente existente (local)', [
                'estudiante_id' => $estudiante->id,
                'codigo' => $codigoContribuyente,
            ]);
        } else {
            // 2. Si no tiene código local, verificar en Oracle (puede existir pero no guardado)
            try {
                $codigoContribuyente = $oracle->verificarContribuyenteExistente($estudiante->nro_documento);
                
                if ($codigoContribuyente) {
                    // Actualizar localmente si existe en Oracle pero no localmente
                    $estudiante->codigo_contribuyente = $codigoContribuyente;
                    $estudiante->save();
                    
                    Log::info('Código de contribuyente sincronizado desde Oracle', [
                        'estudiante_id' => $estudiante->id,
                        'codigo' => $codigoContribuyente,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Error verificando contribuyente existente', [
                    'estudiante_id' => $estudiante->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            // 3. Si no existe en Oracle, crear nuevo contribuyente
            if (!$codigoContribuyente) {
                try {
                    $codigoContribuyente = $oracle->crearContribuyente($estudiante);
                    
                    if ($codigoContribuyente) {
                        $estudiante->codigo_contribuyente = $codigoContribuyente;
                        $estudiante->save();
                        
                        Notification::make()
                            ->success()
                            ->title('Contribuyente creado')
                            ->body("Código: {$codigoContribuyente}")
                            ->send();
                            
                        Log::info('Contribuyente creado al matricular', [
                            'estudiante_id' => $estudiante->id,
                            'matricula_id' => $this->record->id,
                            'codigo' => $codigoContribuyente,
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
                        
                    return; // No podemos generar liquidaciones sin contribuyente
                }
            }
        }
        
        // 4. IMPORTANTE: Regenerar liquidaciones para pagos que no tienen
        if ($codigoContribuyente) {
            $this->regenerarLiquidacionesPendientes($oracle, $codigoContribuyente);
        }
    }
    
    /**
     * Regenera los números de liquidación para pagos que no los tienen.
     */
    protected function regenerarLiquidacionesPendientes(OracleTusneService $oracle, string $codigoContribuyente): void
    {
        $cronograma = $this->record->cronograma;
        
        if (!$cronograma) {
            return;
        }
        
        // Obtener código de especialidad
        $codigoEspecialidad = $this->obtenerCodigoEspecialidad();
        
        if (!$codigoEspecialidad) {
            Log::warning('No se pudo obtener código de especialidad para generar liquidaciones', [
                'matricula_id' => $this->record->id,
            ]);
            return;
        }
        
        // Buscar pagos sin número de liquidación
        $pagosSinLiquidacion = $cronograma->pagos()->whereNull('num_liquidacion')->get();
        
        if ($pagosSinLiquidacion->isEmpty()) {
            return; // Todos los pagos ya tienen liquidación
        }
        
        $liquidacionesGeneradas = 0;
        
        foreach ($pagosSinLiquidacion as $pago) {
            try {
                $numLiquidacion = $oracle->generarCodigoLiquidacion(
                    $codigoEspecialidad,
                    $codigoContribuyente
                );
                
                if ($numLiquidacion) {
                    $pago->update([
                        'num_liquidacion' => $numLiquidacion,
                        'fecha_liquidacion' => now(),
                    ]);
                    $liquidacionesGeneradas++;
                }
            } catch (\Exception $e) {
                Log::error("Error regenerando liquidación para pago {$pago->id}: " . $e->getMessage());
            }
        }
        
        if ($liquidacionesGeneradas > 0) {
            Notification::make()
                ->success()
                ->title('Liquidaciones generadas')
                ->body("Se generaron {$liquidacionesGeneradas} números de liquidación")
                ->send();
                
            Log::info('Liquidaciones regeneradas después de crear contribuyente', [
                'matricula_id' => $this->record->id,
                'cantidad' => $liquidacionesGeneradas,
            ]);
        }
    }
    
    /**
     * Obtiene el código de especialidad para la matrícula.
     */
    protected function obtenerCodigoEspecialidad(): ?string
    {
        $especialidad = null;
        
        if ($this->record->tipo_matricula === \App\Enums\TipoMatricula::CURSO) {
            $especialidad = $this->record->curso?->programa?->especialidad;
        } else {
            $especialidad = $this->record->horario?->programa?->especialidad;
        }
        
        if (!$especialidad || !$especialidad->nombre_especialidad) {
            return null;
        }
        
        $nombreNormalizado = strtolower(trim($especialidad->nombre_especialidad));
        
        $mapeo = [
            'estética personal' => 'B0001',
            'estetica personal' => 'B0001',
            'confección textil' => 'B0002',
            'confeccion textil' => 'B0002',
            'textil y confección' => 'B0002',
            'textil y confeccion' => 'B0002',
            'ofimática' => 'B0003',
            'ofimatica' => 'B0003',
            'computación e informática' => 'B0003',
            'computacion e informatica' => 'B0003',
            'computación' => 'B0003',
            'computacion' => 'B0003',
            'informática' => 'B0003',
            'informatica' => 'B0003',
        ];
        
        return $mapeo[$nombreNormalizado] ?? null;
    }
}
