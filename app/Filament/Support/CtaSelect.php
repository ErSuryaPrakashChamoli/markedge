<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Select;

class CtaSelect
{
    public static function make(string $name = 'cta_id', string $label = 'Call to action'): Select
    {
        return Select::make($name)
            ->label($label)
            ->relationship('cta', 'name')
            ->searchable()
            ->preload()
            ->native(false)
            ->helperText('Leave empty to use the default CTA from Global Settings.');
    }
}
