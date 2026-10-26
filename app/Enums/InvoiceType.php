<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceType: string implements HasColor, HasLabel
{
    case SHIPPING = 'shipping';
    case CLEARANCE = 'clearance';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SHIPPING => 'Shipping',
            self::CLEARANCE => 'Clearance',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SHIPPING => 'info',
            self::CLEARANCE => 'warning',
        };
    }
}
