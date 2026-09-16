<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class Comparison extends Block
{
    public function key(): string
    {
        return 'comparison';
    }

    public function label(): string
    {
        return 'Comparison table';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedSwatch;
    }

    public function hosts(): array
    {
        return ['product', 'landing_page', 'page'];
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::intro(),
            Repeater::make('columns')->simple(TextInput::make('label')->required()->maxLength(60))->minItems(1)->maxItems(4)->label('Column headings')->columnSpanFull(),
            Repeater::make('rows')->schema([
                TextInput::make('label')->required()->maxLength(120),
                Repeater::make('values')->simple(TextInput::make('value')->maxLength(120))->minItems(1)->maxItems(4)->label('Values (one per column)'),
            ])->minItems(1)->maxItems(20)->collapsible()->itemLabel(fn (array $state): ?string => $state['label'] ?? null)->columnSpanFull(),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'columns' => ['required', 'array', 'min:1', 'max:4'],
            'columns.*' => ['string', 'max:60'],
            'rows' => ['required', 'array', 'min:1', 'max:20'],
            'rows.*.label' => ['required', 'string', 'max:120'],
            'rows.*.values' => ['required', 'array', 'min:1', 'max:4'],
            'rows.*.values.*' => ['nullable', 'string', 'max:120'],
        ];
    }
}
