<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DistritoLima: string implements HasLabel
{
    case ANCON                  = 'Ancón';
    case ATE                    = 'Ate';
    case BARRANCO               = 'Barranco';
    case BRENA                  = 'Breña';
    case CARABAYLLO             = 'Carabayllo';
    case CHACLACAYO             = 'Chaclacayo';
    case CHORRILLOS             = 'Chorrillos';
    case CIENEGUILLA            = 'Cieneguilla';
    case COMAS                  = 'Comas';
    case EL_AGUSTINO            = 'El Agustino';
    case INDEPENDENCIA          = 'Independencia';
    case JESUS_MARIA            = 'Jesús María';
    case LA_MOLINA              = 'La Molina';
    case LA_VICTORIA            = 'La Victoria';
    case LIMA                   = 'Lima';
    case LINCE                  = 'Lince';
    case LOS_OLIVOS             = 'Los Olivos';
    case LURIGANCHO             = 'Lurigancho';
    case LURIN                  = 'Lurín';
    case MAGDALENA_DEL_MAR      = 'Magdalena del Mar';
    case MIRAFLORES             = 'Miraflores';
    case PACHACAMAC             = 'Pachacámac';
    case PUCUSANA               = 'Pucusana';
    case PUEBLO_LIBRE           = 'Pueblo Libre';
    case PUENTE_PIEDRA          = 'Puente Piedra';
    case PUNTA_HERMOSA          = 'Punta Hermosa';
    case PUNTA_NEGRA            = 'Punta Negra';
    case RIMAC                  = 'Rímac';
    case SAN_BARTOLO            = 'San Bartolo';
    case SAN_BORJA              = 'San Borja';
    case SAN_ISIDRO             = 'San Isidro';
    case SAN_JUAN_DE_LURIGANCHO = 'San Juan de Lurigancho';
    case SAN_JUAN_DE_MIRAFLORES = 'San Juan de Miraflores';
    case SAN_LUIS               = 'San Luis';
    case SAN_MARTIN_DE_PORRES   = 'San Martín de Porres';
    case SAN_MIGUEL             = 'San Miguel';
    case SANTA_ANITA            = 'Santa Anita';
    case SANTA_MARIA_DEL_MAR    = 'Santa María del Mar';
    case SANTA_ROSA             = 'Santa Rosa';
    case SANTIAGO_DE_SURCO      = 'Santiago de Surco';
    case SURQUILLO              = 'Surquillo';
    case VILLA_EL_SALVADOR      = 'Villa El Salvador';
    case VILLA_MARIA_DEL_TRIUNFO= 'Villa María del Triunfo';

    public function getLabel(): string
    {
        return $this->value;
    }
}
