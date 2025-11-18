<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoOfertaAcademica: string implements HasLabel
{
    case PROG_ESTUDIO  = 'PROG_ESTUDIO';
    case PROG_CONTINUA = 'PROG_CONTINUA';
    case CURSO_LIBRE   = 'CURSO_LIBRE';

    public function getLabel(): string
    {
        return match ($this) {
            self::PROG_ESTUDIO  => 'Programa de Estudio',
            self::PROG_CONTINUA => 'Programa de Formación Continua',
            self::CURSO_LIBRE   => 'Curso Libre',
        };
    }
}
