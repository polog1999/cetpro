<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum GradoInstruccionEBR: string implements HasLabel
{
    case SIN_NIVEL = 'Sin nivel';
    case PRIMARIA_INCOMPLETA = 'Primaria incompleta';
    case PRIMARIA_COMPLETA = 'Primaria completa';
    case SECUNDARIA_INCOMPLETA = 'Secundaria incompleta';
    case SECUNDARIA_COMPLETA = 'Secundaria completa';
    case SUPERIOR_INCOMPLETA = 'Superior incompleta';
    case SUPERIOR_COMPLETA = 'Superior completa';

    public function getLabel(): string
    {
        return $this->value;
    }
}
