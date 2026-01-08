<?php

namespace App\Filament\Resources\Pagos\Pages;

use App\Filament\Resources\Pagos\PagoResource;
use Filament\Resources\Pages\ListRecords;
use App\Models\Pago;
use App\Services\OracleTusneService;

class ListPagos extends ListRecords
{
    protected static string $resource = PagoResource::class;

    /**
     * Sincroniza estados de pago desde Oracle cuando se carga la página.
     */
    public function mount(): void
    {
        parent::mount();
        
        $this->sincronizarConOracle();
    }

    /**
     * Sincroniza los estados de todos los pagos con Oracle.
     */
    protected function sincronizarConOracle(): void
    {
        try {
            $oracleService = app(OracleTusneService::class);
            
            // Verificar conexión Oracle antes de proceder
            if (!$oracleService->verificarConexion()) {
                \Log::warning('Oracle no disponible para sincronizar en ListPagos');
                return;
            }

            // Obtener todos los pagos que tienen num_liquidacion
            $pagos = Pago::whereNotNull('num_liquidacion')
                ->where('num_liquidacion', '!=', '')
                ->get();

            $sincronizados = 0;
            foreach ($pagos as $pago) {
                try {
                    $estadoOracle = $oracleService->obtenerEstadoLiquidacion($pago->num_liquidacion);
                    
                    if ($estadoOracle && $estadoOracle !== $pago->estado) {
                        $pago->update(['estado' => $estadoOracle]);
                        $sincronizados++;
                    }
                } catch (\Exception $e) {
                    // Continuar con el siguiente pago
                    continue;
                }
            }

            if ($sincronizados > 0) {
                \Log::info("ListPagos: Sincronizados {$sincronizados} pagos desde Oracle");
            }
        } catch (\Exception $e) {
            // Fallo silencioso - no interrumpir la carga de la página
            \Log::warning('Error sincronizando con Oracle en ListPagos: ' . $e->getMessage());
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // Botón de crear deshabilitado porque los pagos se generan automáticamente
        ];
    }
}
