<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LenguaMaterna: string implements HasLabel
{
    case CASTELLANO = 'Castellano';
    case QUECHUA = 'Quechua';
    case AIMARA = 'Aimara';
    case ASHANINKA = 'Asháninka';
    case OTRA_LENGUA_ORIGINARIA = 'Otra lengua originaria';
    case LENGUA_EXTRANJERA = 'Lengua extranjera';
    case LENGUA_SENAS_PERUANA = 'Lengua de señas peruana';

    public function getLabel(): string
    {
        return $this->value;
    }
}
