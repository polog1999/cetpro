<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoProgramaReparacion: string implements HasLabel
{
    case NINGUNO = 'Ninguno';
    case VICTIMA_DIRECTA = 'Víctima directa (Inscrito en RUV)';
    case TRANSFERENCIA_BENEFICIOS = 'Transferencia de beneficios (REBRED)';
    case OTROS_PROGRAMAS_ESTADO = 'Otros programas del Estado';

    public function getLabel(): string
    {
        return $this->value;
    }
}
