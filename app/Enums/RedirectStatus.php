<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RedirectStatus: int implements HasLabel
{
    case MovedPermanently = 301;
    case Found = 302;
    case TemporaryRedirect = 307;
    case PermanentRedirect = 308;

    public function getLabel(): string
    {
        return match ($this) {
            self::MovedPermanently => '301 Moved permanently',
            self::Found => '302 Found',
            self::TemporaryRedirect => '307 Temporary redirect',
            self::PermanentRedirect => '308 Permanent redirect',
        };
    }
}
