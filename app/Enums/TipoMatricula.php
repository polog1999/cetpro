<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoMatricula: string implements HasLabel, HasColor
{
    case PROG_ESTUDIO   = 'Programa de estudio';
    case FORM_CONTINUA  = 'Programa de formación continua';
    case CURSO_LIBRE    = 'Curso libre';

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PROG_ESTUDIO  => 'primary',
            self::FORM_CONTINUA => 'success',
            self::CURSO_LIBRE   => 'info',
        };
    }
}
