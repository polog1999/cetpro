<?php

namespace App\Enums;

enum TipoOfertaAcademica: string
{
    case PROG_ESTUDIO  = 'PROG_ESTUDIO';
    case PROG_CONTINUA = 'PROG_CONTINUA';
    case CURSO_LIBRE   = 'CURSO_LIBRE';

    /**
     * Label amigable para usar en Filament / selects.
     */
    public function label(): string
    {
        return match ($this) {
            self::PROG_ESTUDIO  => 'Programa de Estudio',
            self::PROG_CONTINUA => 'Programa de Formación Continua',
            self::CURSO_LIBRE   => 'Curso Libre',
        };
    }

    /**
     * Útil si quieres un array para selects:
     * TipoOfertaAcademica::options()
     */
    public static function options(): array
    {
        return [
            self::PROG_ESTUDIO->value  => self::PROG_ESTUDIO->label(),
            self::PROG_CONTINUA->value => self::PROG_CONTINUA->label(),
            self::CURSO_LIBRE->value   => self::CURSO_LIBRE->label(),
        ];
    }
}
