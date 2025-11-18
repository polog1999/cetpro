<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum GradoInstruccion: string implements HasLabel
{
    case SIN_ESTUDIOS            = 'Sin estudios';
    case PRIMARIA_INCOMPLETA     = 'Primaria incompleta';
    case PRIMARIA_COMPLETA       = 'Primaria completa';
    case SECUNDARIA_INCOMPLETA   = 'Secundaria incompleta';
    case SECUNDARIA_COMPLETA     = 'Secundaria completa';
    case SUPERIOR_TECNICA        = 'Superior técnica';
    case SUPERIOR_UNIVERSITARIA  = 'Superior universitaria';
    case POSGRADO               = 'Posgrado';

    public function getLabel(): string
    {
        return $this->value;
    }
}
