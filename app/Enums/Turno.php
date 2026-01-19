<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Turno: string implements HasLabel
{
    case MAÑANA = 'Mañana';
    case TARDE = 'Tarde';
    case NOCHE = 'Noche';

    public function getLabel(): string
    {
        return $this->value;
    }
}
