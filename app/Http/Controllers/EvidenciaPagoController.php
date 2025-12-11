<?php

namespace App\Http\Controllers;

use App\Services\EvidenciaPagoService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EvidenciaPagoController extends Controller
{
    protected EvidenciaPagoService $evidenciaService;

    public function __construct(EvidenciaPagoService $evidenciaService)
    {
        $this->evidenciaService = $evidenciaService;
    }

    /**
     * Descarga una evidencia de pago.
     */
    public function descargar(Request $request, int $pagoId)
    {
        try {
            $usuarioId = auth()->id();
            
            if (!$usuarioId) {
                abort(401, 'No autenticado');
            }

            return $this->evidenciaService->descargarEvidencia($pagoId, $usuarioId);
        } catch (ValidationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            abort(500, 'Error al descargar la evidencia');
        }
    }

    /**
     * Visualiza una evidencia de pago.
     */
    public function visualizar(Request $request, int $pagoId)
    {
        try {
            $usuarioId = auth()->id();
            
            if (!$usuarioId) {
                abort(401, 'No autenticado');
            }

            $url = $this->evidenciaService->obtenerUrlVisualizacion($pagoId, $usuarioId);
            
            return redirect($url);
        } catch (ValidationException $e) {
            abort(403, $e->getMessage());
        } catch (\Exception $e) {
            abort(500, 'Error al visualizar la evidencia');
        }
    }
}
