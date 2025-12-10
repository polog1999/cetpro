<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasIcon;

enum EstadoPago: string implements HasColor, HasIcon, HasLabel
{
    case PENDIENTE = 'pendiente';
    case PAGADO = 'pagado';
    case VENCIDO = 'vencido';
    case ANULADO = 'anulado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::PAGADO => 'Pagado',
            self::VENCIDO => 'Vencido',
            self::ANULADO => 'Anulado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDIENTE => 'warning',  // Amarillo
            self::PAGADO => 'success',     // Verde
            self::VENCIDO => 'danger',     // Rojo
            self::ANULADO => 'gray',       // Gris
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDIENTE => 'heroicon-o-clock',
            self::PAGADO => 'heroicon-o-check-circle',
            self::VENCIDO => 'heroicon-o-exclamation-triangle',
            self::ANULADO => 'heroicon-o-x-circle',
        };
    }

    /**
     * Verifica si el estado permite realizar pagos.
     */
    public function puedeSerPagado(): bool
    {
        return in_array($this, [self::PENDIENTE, self::VENCIDO]);
    }

    /**
     * Verifica si el estado es final (no puede cambiar).
     */
    public function esFinal(): bool
    {
        return in_array($this, [self::PAGADO, self::ANULADO]);
    }
}
