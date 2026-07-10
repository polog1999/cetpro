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
use Filament\Actions\Action;

class CreateMatricula extends CreateRecord
{
    protected static string $resource = MatriculaResource::class;

    /**
     * Modifica el botón nativo de guardar de Filament para que 
     * exija una confirmación antes de procesar el formulario.
     */
    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Crear Matrícula')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('warning')
            ->modalHeading('Confirmar Matrícula')
            ->modalDescription('¿Está seguro que desea crear esta matrícula? Esta acción generará el cronograma de pagos y las liquidaciones correspondientes.')
            ->modalSubmitActionLabel('Sí, crear matrícula')
            ->modalCancelActionLabel('Cancelar');
    }

    /**
     * Maneja la creación del registro de forma segura.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $service = app(MatriculaService::class);

        // 1. Validar deudas del estudiante
        $validacionDeudas = $service->estudianteTieneDeudas($data['estudiante_id']);
        
        if ($validacionDeudas['tiene_deuda']) {
            // Esto detendrá el spinner y pintará el error en rojo en el formulario
            throw ValidationException::withMessages([
                'estudiante_id' => $validacionDeudas['mensaje']
            ]);
        }
        
        try {
            // 2. Crear la matrícula usando el modelo nativo
            return static::getModel()::create($data);
        } catch (\Exception $e) {
            Log::error('Error en base de datos al matricular', ['error' => $e->getMessage()]);
            
            throw ValidationException::withMessages([
                'estudiante_id' => 'Error al procesar la matrícula: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Procesos post-creación (Oracle) totalmente protegidos de caídas o lentitud.
     */
    protected function afterCreate(): void
    {
        try {
            $estudiante = $this->record->estudiante;
            if (!$estudiante) return;

            $oracle = app(OracleTusneService::class);
            $codigoContribuyente = null;
            
            // Verificación local
            if (!empty($estudiante->codigo_contribuyente)) {
                $codigoContribuyente = $estudiante->codigo_contribuyente;
            } else {
                // Consulta externa segura
                try {
                    $codigoContribuyente = $oracle->verificarContribuyenteExistente($estudiante->nro_documento);
                    
                    if ($codigoContribuyente) {
                        $estudiante->codigo_contribuyente = $codigoContribuyente;
                        $estudiante->save();
                    } else {
                        $codigoContribuyente = $oracle->crearContribuyente($estudiante);
                        if ($codigoContribuyente) {
                            $estudiante->codigo_contribuyente = $codigoContribuyente;
                            $estudiante->save();
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Fallo de conexión con Oracle en afterCreate', ['error' => $e->getMessage()]);
                    
                    Notification::make()
                        ->warning()
                        ->title('Matrícula guardada (Oracle Pendiente)')
                        ->body('No se pudo conectar con Oracle. Las liquidaciones se generarán más tarde.')
                        ->persistent()
                        ->send();
                    return;
                }
            }
            
            // Generar liquidaciones
            if ($codigoContribuyente) {
                try {
                    $this->regenerarLiquidacionesPendientes($oracle, $codigoContribuyente);
                } catch (\Exception $e) {
                    Log::error('Error generando liquidaciones', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Exception $e) {
            Log::critical('Error general en afterCreate', ['error' => $e->getMessage()]);
        }
    }
    
    protected function regenerarLiquidacionesPendientes(OracleTusneService $oracle, string $codigoContribuyente): void
    {
        $cronograma = $this->record->cronograma;
        if (!$cronograma) return;
        
        $codigoEspecialidad = $this->obtenerCodigoEspecialidad();
        if (!$codigoEspecialidad) return;
        
        $pagosSinLiquidacion = $cronograma->pagos()->whereNull('num_liquidacion')->get();
        if ($pagosSinLiquidacion->isEmpty()) return;
        
        $liquidacionesGeneradas = 0;
        
        foreach ($pagosSinLiquidacion as $pago) {
            try {
                $numLiquidacion = $oracle->generarCodigoLiquidacion($codigoEspecialidad, $codigoContribuyente);
                if ($numLiquidacion) {
                    $pago->update([
                        'num_liquidacion' => $numLiquidacion,
                        'fecha_liquidacion' => now(),
                    ]);
                    $liquidacionesGeneradas++;
                }
            } catch (\Exception $e) {
                Log::error("Error liquidación pago {$pago->id}: " . $e->getMessage());
            }
        }
        
        if ($liquidacionesGeneradas > 0) {
            Notification::make()
                ->success()
                ->title('Liquidaciones generadas')
                ->body("Se generaron {$liquidacionesGeneradas} números de liquidación")
                ->send();
        }
    }
    
    protected function obtenerCodigoEspecialidad(): ?string
    {
        $especialidad = $this->record->tipo_matricula === \App\Enums\TipoMatricula::CURSO 
            ? $this->record->curso?->programa?->especialidad 
            : $this->record->horario?->programa?->especialidad;
        
        if (!$especialidad || !$especialidad->nombre_especialidad) return null;
        
        $nombreNormalizado = strtolower(trim($especialidad->nombre_especialidad));
        
        $mapeo = [
            'estética personal' => 'B0001', 'estetica personal' => 'B0001',
            'confección textil' => 'B0002', 'confeccion textil' => 'B0002', 'textil y confección' => 'B0002', 'textil y confeccion' => 'B0002',
            'ofimática' => 'B0003', 'ofimatica' => 'B0003', 'computación e informática' => 'B0003', 'computacion e informatica' => 'B0003', 'computación' => 'B0003', 'computacion' => 'B0003', 'informática' => 'B0003', 'informatica' => 'B0003',
        ];
        
        return $mapeo[$nombreNormalizado] ?? null;
    }

    protected function getRedirectUrl(): string
    {
        return MatriculaResource::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Matrícula creada correctamente')
            ->body('El cronograma de pagos se ha generado.')
            ->actions([
                Action::make('descargar')
                    ->label('📥 Descargar Cronograma PDF')
                    ->button()
                    ->color('success')
                    ->url(route('matriculas.cronograma-pdf', ['matricula' => $this->record->id]), shouldOpenInNewTab: true),
            ])
            ->persistent();
    }
}