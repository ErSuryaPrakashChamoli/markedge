<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CtaAction: string implements HasLabel
{
    case Url = 'url';
    case Route = 'route';
    case Form = 'form';
    case Whatsapp = 'whatsapp';
    case Phone = 'phone';
    case Email = 'email';

    public function getLabel(): string
    {
        return match ($this) {
            self::Url => 'Link to a URL',
            self::Route => 'Link to a site page',
            self::Form => 'Open a form',
            self::Whatsapp => 'WhatsApp message',
            self::Phone => 'Phone call',
            self::Email => 'Email',
        };
    }
}
