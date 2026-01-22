<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoMatricula: string implements HasLabel, HasColor
{
    case PROGRAMA           = 'Programa';
    case FORMACION_CONTINUA = 'Formación continua';
    case CURSO              = 'Curso';
    case MODULO             = 'Módulo';
    case UNIDAD             = 'Unidad';

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PROGRAMA           => 'primary',
            self::FORMACION_CONTINUA => 'success',
            self::CURSO              => 'info',
            self::MODULO             => 'warning',
            self::UNIDAD             => 'gray',
        };
    }
}
