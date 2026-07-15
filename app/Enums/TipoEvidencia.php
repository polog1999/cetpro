<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor; // <-- Importar esto
enum TipoEvidencia: string implements HasLabel, HasColor
{
   
    case ACTA_EVALUACION = 'ACTA_EVALUACION';
    case NOMINA_MATRICULA = 'NOMINA_MATRICULA';
    case PORTAFOLIO_DOCENTE = 'PORTAFOLIO_DOCENTE';
    case INFORME_FINAL = 'INFORME_FINAL';
    
    public function getLabel(): string
    {
        return match ($this) {
            self::ACTA_EVALUACION => 'Acta de Evaluación',
            self::NOMINA_MATRICULA   => 'Nomina de Matrícula',
            self::PORTAFOLIO_DOCENTE => 'Portafolio de Evidencias',
            self::INFORME_FINAL => 'Informe Final',
        };
    }

    public function getColor(): string | array
    {
        return match ($this) {
            self::ACTA_EVALUACION => 'warning',
            self::NOMINA_MATRICULA => 'success',
            self::PORTAFOLIO_DOCENTE => 'info',
            self::INFORME_FINAL => Color::Purple,
        };
    }

}
