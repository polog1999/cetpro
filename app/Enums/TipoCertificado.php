<?php

namespace App\Enums;

enum TipoCertificado: string
{
    case CERTIFICADO_ESTUDIOS = 'certificado_estudios';
    case CONSTANCIA = 'constancia';
    case DIPLOMA = 'diploma';
    
    /**
     * Etiqueta para mostrar en UI
     */
    public function getLabel(): string
    {
        return match($this) {
            self::CERTIFICADO_ESTUDIOS => 'Certificado de Estudios',
            self::CONSTANCIA => 'Constancia',
            self::DIPLOMA => 'Diploma',
        };
    }
    
    /**
     * Color para badges
     */
    public function getColor(): string
    {
        return match($this) {
            self::CERTIFICADO_ESTUDIOS => 'success',
            self::CONSTANCIA => 'info',
            self::DIPLOMA => 'warning',
        };
    }
}
