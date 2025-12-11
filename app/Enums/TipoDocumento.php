<?php

namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum TipoDocumento: string implements HasLabel
{
    case DNI = 'DNI';
    case CARNET_EXTRANJERIA = 'Carnet de extranjeria';
    case PASAPORTE = 'Pasaporte';
    case PTP = 'PTP';
    case RUC = 'RUC';

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getMaxLength(): int
    {
        return match ($this) {
            self::DNI => 8,
            self::RUC => 11,
            self::CARNET_EXTRANJERIA, self::PTP, self::PASAPORTE => 12,
        };
    }

    public function isNumeric(): bool
    {
        return match ($this) {
            self::PASAPORTE => false,
            default => true,
        };
    }
}
