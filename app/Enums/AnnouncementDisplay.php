<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AnnouncementDisplay: string implements HasLabel
{
    case Bar = 'bar';
    case Popup = 'popup';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bar => 'Announcement bar',
            self::Popup => 'Popup',
        };
    }
}
