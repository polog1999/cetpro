<?php

namespace App\Filament\Resources\Pagos\Pages;

use App\Filament\Resources\Pagos\PagoResource;
use Filament\Resources\Pages\ListRecords;
use App\Models\Pago;
use App\Services\OracleTusneService;
use Filament\Notifications\Notification;

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
     * Sincroniza los estados y fechas de pago de todos los pagos con Oracle.
     */
    protected function sincronizarConOracle(): void
    {
        try {
            $oracleService = app(OracleTusneService::class);
            
            // Verificar conexión Oracle antes de proceder
            if (!$oracleService->verificarConexion()) {
                return;
            }

            // Obtener todos los pagos que tienen num_liquidacion
            $pagos = Pago::whereNotNull('num_liquidacion')
                ->where('num_liquidacion', '!=', '')
                ->get();

            $sincronizados = 0;
            foreach ($pagos as $pago) {
                try {
                    $datosOracle = $oracleService->obtenerDatosLiquidacion($pago->num_liquidacion);
                    
                    if ($datosOracle) {
                        $cambios = [];
                        
                        // Actualizar estado
                        if ($datosOracle->ESTADO !== null && $datosOracle->ESTADO !== $pago->estado) {
                            $cambios['estado'] = $datosOracle->ESTADO;
                        }
                        
                        // Actualizar fecha de pago
                        if (!empty($datosOracle->PAGADO)) {
                            try {
                                $fechaPago = \Carbon\Carbon::createFromFormat('d/m/Y', $datosOracle->PAGADO);
                                if ($pago->fecha_pago != $fechaPago) {
                                    $cambios['fecha_pago'] = $fechaPago;
                                }
                            } catch (\Exception $e) {
                                try {
                                    $fechaPago = \Carbon\Carbon::parse($datosOracle->PAGADO);
                                    if ($pago->fecha_pago != $fechaPago) {
                                        $cambios['fecha_pago'] = $fechaPago;
                                    }
                                } catch (\Exception $e2) {}
                            }
                        } else {
                            // Oracle tiene NULL, limpiar en PostgreSQL
                            if ($pago->fecha_pago !== null) {
                                $cambios['fecha_pago'] = null;
                            }
                        }
                        
                        if (!empty($cambios)) {
                            $pago->update($cambios);
                            $sincronizados++;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            if ($sincronizados > 0) {
                Notification::make()
                    ->success()
                    ->title('Sincronización Oracle')
                    ->body("Se actualizaron {$sincronizados} pago(s) desde Oracle.")
                    ->send();
            }
        } catch (\Exception $e) {
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

