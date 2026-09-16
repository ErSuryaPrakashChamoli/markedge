<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PageTemplate: string implements HasLabel
{
    case Default = 'default';
    case Home = 'home';
    case About = 'about';
    case Contact = 'contact';
    case Form = 'form';
    case Legal = 'legal';
    case Careers = 'careers';

    public function getLabel(): string
    {
        return match ($this) {
            self::Default => 'Default',
            self::Home => 'Home page',
            self::About => 'About',
            self::Contact => 'Contact',
            self::Form => 'Form page',
            self::Legal => 'Legal document',
            self::Careers => 'Careers',
        };
    }
}
