<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CicloFormacion: string implements HasLabel
{
    case AUXILIAR_TECNICO = 'Auxiliar técnico';
    case TECNICO = 'Técnico';

    public function getLabel(): string
    {
        return $this->value;
    }
}
