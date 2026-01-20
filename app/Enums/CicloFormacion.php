<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Ciclo de formación para CETPRO.
 * 
 * Según el currículo de Educación Técnico-Productiva:
 * - Auxiliar Técnico: Certificación básica
 * - Técnico: Formación completa
 */
enum CicloFormacion: string implements HasLabel
{
    case AUXILIAR_TECNICO = 'Auxiliar técnico';
    case TECNICO = 'Técnico';

    public function getLabel(): string
    {
        return $this->value;
    }
}
