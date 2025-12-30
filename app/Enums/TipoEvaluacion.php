<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoEvaluacion: string implements HasLabel
{
    case PRACTICA = 'practica';
    case TEORIA = 'teoria';
    case PROYECTO = 'proyecto';
    case EXAMEN_PARCIAL = 'parcial';
    case EXAMEN_FINAL = 'final';
    case RECUPERACION = 'recuperacion';
    case SUBSANACION = 'subsanacion';

    public function getLabel(): string
    {
        return match ($this) {
            self::PRACTICA => 'Práctica',
            self::TEORIA => 'Teoría',
            self::PROYECTO => 'Proyecto',
            self::EXAMEN_PARCIAL => 'Examen Parcial',
            self::EXAMEN_FINAL => 'Examen Final',
            self::RECUPERACION => 'Recuperación',
            self::SUBSANACION => 'Subsanación',
        };
    }
}

