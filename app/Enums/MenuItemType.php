<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MenuItemType: string implements HasLabel
{
    case Url = 'url';
    case Entity = 'entity';
    case Heading = 'heading';

    public function getLabel(): string
    {
        return match ($this) {
            self::Url => 'Custom URL',
            self::Entity => 'Site content',
            self::Heading => 'Heading (no link)',
        };
    }
}
