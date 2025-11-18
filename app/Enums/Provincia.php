<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Provincia: string implements HasLabel
{
    case LIMA = 'Lima';

    public function getLabel(): string
    {
        return $this->value;
    }
}
