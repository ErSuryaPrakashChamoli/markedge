<?php

namespace App\Filament\Support;

use App\Rules\NotReservedSlug;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

/**
 * Title/name + slug pair. The slug is suggested while creating and never touched on edit,
 * so URLs stay stable (architecture §34).
 */
class SlugField
{
    public static function source(string $name = 'name', string $label = 'Name'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->required()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation): void {
                if ($operation === 'create' && blank($get('slug'))) {
                    $set('slug', Str::slug((string) $state));
                }
            });
    }

    /**
     * @param  array<int, mixed>  $extraRules
     */
    public static function make(bool $reserved = false, array $extraRules = []): TextInput
    {
        $rules = $extraRules;

        if ($reserved) {
            $rules[] = new NotReservedSlug;
        }

        return TextInput::make('slug')
            ->maxLength(191)
            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
            ->unique(ignoreRecord: true)
            ->rules($rules)
            ->helperText('Lowercase letters, numbers and dashes. Leave empty to generate it. Changing a published slug changes the public URL.')
            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug($state) : null);
    }
}
