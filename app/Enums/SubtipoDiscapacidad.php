<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SubtipoDiscapacidad: string implements HasLabel
{
    // Subtipos para discapacidad AUDITIVA
    case HIPOACUSIA = 'Hipoacusia';
    case SORDERA_TOTAL = 'Sordera total';
    
    // Subtipos para discapacidad VISUAL
    case BAJA_VISION = 'Baja visión';
    case CEGUERA = 'Ceguera';

    public function getLabel(): string
    {
        return $this->value;
    }

    /**
     * Obtiene los subtipos disponibles según el tipo de discapacidad
     */
    public static function getSubtiposPorTipo(TipoDiscapacidad $tipo): array
    {
        return match ($tipo) {
            TipoDiscapacidad::AUDITIVA => [
                self::HIPOACUSIA,
                self::SORDERA_TOTAL,
            ],
            TipoDiscapacidad::VISUAL => [
                self::BAJA_VISION,
                self::CEGUERA,
            ],
            default => [],
        };
    }

    /**
     * Obtiene opciones para Select filtradas por tipo de discapacidad
     */
    public static function getOptionsPorTipo(?string $tipo): array
    {
        if (!$tipo) {
            return [];
        }

        $tipoEnum = TipoDiscapacidad::tryFrom($tipo);
        if (!$tipoEnum) {
            return [];
        }

        $subtipos = self::getSubtiposPorTipo($tipoEnum);
        $options = [];
        
        foreach ($subtipos as $subtipo) {
            $options[$subtipo->value] = $subtipo->getLabel();
        }
        
        return $options;
    }
}
