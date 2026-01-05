<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Calificaciones en letra según escala de logros.
 */
enum CalificacionLetra: string implements HasLabel
{
    case AD = 'AD';  // Logro destacado
    case A = 'A';    // Logro esperado
    case B = 'B';    // En proceso
    case C = 'C';    // En inicio

    public function getLabel(): ?string
    {
        return match ($this) {
            self::AD => 'AD - Logro destacado',
            self::A => 'A - Logro esperado',
            self::B => 'B - En proceso',
            self::C => 'C - Iniciando',
        };
    }

    /**
     * Obtiene el color para badges/indicadores.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::AD => 'success',
            self::A => 'info',
            self::B => 'warning',
            self::C => 'danger',
        };
    }
}
