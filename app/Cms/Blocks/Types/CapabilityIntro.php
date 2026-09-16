<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

class CapabilityIntro extends Block
{
    public function key(): string
    {
        return 'capability_intro';
    }

    public function label(): string
    {
        return 'Capability introduction';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedRectangleGroup;
    }

    public function hosts(): array
    {
        return ['page'];
    }

    public function fields(): array
    {
        return [
            Fields::heading(required: true),
            Fields::intro(),
            Toggle::make('show_products')->label('Include the products pillar')->default(true),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['required', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'show_products' => ['sometimes', 'boolean'],
        ];
    }
}
