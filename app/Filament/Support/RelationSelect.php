<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Select;

/**
 * Many-to-many pickers. Small catalogues preload; large ones search.
 */
class RelationSelect
{
    public static function many(string $relationship, string $label, string $titleAttribute = 'name', bool $preload = true): Select
    {
        return Select::make($relationship)
            ->label($label)
            ->relationship($relationship, $titleAttribute)
            ->multiple()
            ->searchable()
            ->preload($preload)
            ->columnSpanFull();
    }
}
