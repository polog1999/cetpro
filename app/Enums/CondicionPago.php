<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CondicionPago: string implements HasLabel, HasIcon, HasColor
{
    case NORMAL = 'normal';
    case BECADO = 'becado';
    case INABIF = 'inabif';

    public function getLabel(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal (Pagante)',
            self::BECADO => 'Becado (Gratuito)',
            self::INABIF => 'Inabif',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::NORMAL => 'heroicon-m-currency-dollar',
            self::BECADO => 'heroicon-m-gift',
            self::INABIF => 'heroicon-m-building-office-2',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NORMAL => 'success',
            self::BECADO => 'info',
            self::INABIF => 'warning',
        };
    }
}