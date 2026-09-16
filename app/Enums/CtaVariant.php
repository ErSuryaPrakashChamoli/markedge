<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CtaVariant: string implements HasLabel
{
    case Band = 'band';
    case Inline = 'inline';
    case Card = 'card';

    public function getLabel(): string
    {
        return match ($this) {
            self::Band => 'Full-width band',
            self::Inline => 'Inline',
            self::Card => 'Card',
        };
    }
}
