<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoDiscapacidad: string implements HasLabel
{
    case NINGUNA = 'Ninguna';
    case INTELECTUAL = 'Intelectual';
    case AUDITIVA = 'Auditiva';
    case VISUAL = 'Visual';
    case FISICA_MOTORA = 'Física motora';
    case TEA = 'Trastorno del Espectro Autista (TEA)';
    case SORDOCEGUERA = 'Sordoceguera';
    case MULTIDISCAPACIDAD = 'Multidiscapacidad';
    case TALENTO_SUPERDOTACION = 'Talento y superdotación';

    public function getLabel(): string
    {
        return $this->value;
    }

    /**
     * Verifica si este tipo de discapacidad tiene subtipos
     */
    public function tieneSubtipos(): bool
    {
        return in_array($this, [self::AUDITIVA, self::VISUAL]);
    }
}
