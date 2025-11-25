<?php

namespace App\Observers;

use App\Models\Pago;

class PagoObserver
{
    /**
     * Handle the Pago "saved" event.
     * Se dispara cuando se crea o actualiza un pago.
     */
    public function saved(Pago $pago): void
    {
        $this->actualizarEstadoMatricula($pago);
    }

    /**
     * Handle the Pago "updated" event.
     * Se dispara cuando se actualiza un pago.
     */
    public function updated(Pago $pago): void
    {
        $this->actualizarEstadoMatricula($pago);
    }

    /**
     * Actualiza el estado de la matrícula asociada al pago.
     */
    protected function actualizarEstadoMatricula(Pago $pago): void
    {
        // Obtener el cronograma del pago
        $cronograma = $pago->cronograma;
        
        if (!$cronograma) {
            return;
        }

        // Obtener la matrícula del cronograma
        $matricula = $cronograma->matricula;

        if (!$matricula) {
            return;
        }

        // Actualizar el estado de la matrícula según el cronograma
        $matricula->actualizarEstadoSegunCronograma();
    }
}
