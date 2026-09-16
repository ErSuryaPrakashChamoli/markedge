<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FormSuccessMode: string implements HasLabel
{
    case Message = 'message';
    case Redirect = 'redirect';

    public function getLabel(): string
    {
        return match ($this) {
            self::Message => 'Show a success message',
            self::Redirect => 'Redirect to a page',
        };
    }
}
