<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoPrograma: string implements HasLabel, HasColor
{
    case PROGRAMA_ESTUDIO      = 'Programa';
    case FORMACION_CONTINUA    = 'Formación continua';

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PROGRAMA_ESTUDIO   => 'primary',
            self::FORMACION_CONTINUA => 'success',
        };
    }
}
