<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FormFieldType: string implements HasLabel
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Email = 'email';
    case Tel = 'tel';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Number = 'number';
    case Date = 'date';
    case Hidden = 'hidden';

    public function getLabel(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Textarea => 'Multi-line text',
            self::Email => 'Email',
            self::Tel => 'Phone',
            self::Select => 'Dropdown',
            self::Multiselect => 'Multi-select',
            self::Radio => 'Radio buttons',
            self::Checkbox => 'Checkbox',
            self::Number => 'Number',
            self::Date => 'Date',
            self::Hidden => 'Hidden',
        };
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::Multiselect, self::Radio], true);
    }
}
