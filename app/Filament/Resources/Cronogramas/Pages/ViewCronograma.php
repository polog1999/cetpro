<?php

namespace App\Filament\Resources\Cronogramas\Pages;

use App\Filament\Resources\Cronogramas\CronogramaResource;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Cronogramas\RelationManagers\PagosRelationManager;
use App\Services\OracleTusneService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;

class ViewCronograma extends ViewRecord
{
    protected static string $resource = CronogramaResource::class;

    /**
     * Sincroniza los pagos con Oracle al cargar la vista.
     */
    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        $this->sincronizarConOracle();
    }

    /**
     * Sincroniza los pagos del cronograma actual con Oracle.
     */
    protected function sincronizarConOracle(): void
    {
        try {
            $oracleService = app(OracleTusneService::class);
            
            if (!$oracleService->verificarConexion()) {
                return; // No mostrar error si Oracle no está disponible
            }
            
            $actualizados = $oracleService->sincronizarPagosCronograma($this->record);
            
            if ($actualizados > 0) {
                Notification::make()
                    ->success()
                    ->title('Sincronización Oracle')
                    ->body("Se actualizaron {$actualizados} pago(s) desde Oracle.")
                    ->send();
            }
        } catch (\Exception $e) {
            \Log::warning('Error sincronizando con Oracle en ViewCronograma: ' . $e->getMessage());
            // No mostrar error al usuario, solo registrar en log
        }
    }

    // Tal como en tu ejemplo, definimos qué relation managers mostrar aquí
    public function getRelationManagers(): array
    {
        return [
            PagosRelationManager::class,
        ];
    }

    // Título grande de la página (Como tu "Agregar cursos")
    public function getHeading(): string|Htmlable
    {
        return 'Gestión de Pagos y Sección';
    }
}