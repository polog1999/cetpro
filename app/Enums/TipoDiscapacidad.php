<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoDiscapacidad: string implements HasLabel
{
    case NINGUNA = 'Ninguna (no tiene discapacidad)';
    case INTELECTUAL = 'Discapacidad intelectual';
    case AUDITIVA = 'Discapacidad auditiva';
    case VISUAL = 'Discapacidad visual';
    case FISICA_MOTORA = 'Discapacidad física o motora';
    case TEA = 'Trastorno del espectro autista';
    case SORDOCEGUERA = 'Sordoceguera';
    case MULTIDISCAPACIDAD = 'Multidiscapacidad';
    case TALENTO_SUPERDOTACION = 'Talento y superdotación (altas capacidades)';
    case OTRA = 'Otra discapacidad u otra condición';

    public function getLabel(): string
    {
        return $this->value;
    }

    /**
     * Verifica si este tipo de discapacidad tiene subtipos
     */
    public function tieneSubtipos(): bool
    {
        return in_array($this, [self::INTELECTUAL, self::AUDITIVA, self::VISUAL]);
    }
}
