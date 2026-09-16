<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class Timeline extends Block
{
    public function key(): string
    {
        return 'timeline';
    }

    public function label(): string
    {
        return 'Timeline';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedClock;
    }

    public function hosts(): array
    {
        return ['page', 'product', 'case_study', 'landing_page'];
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Repeater::make('entries')->schema([
                TextInput::make('date_label')->label('Date or phase')->required()->maxLength(60),
                TextInput::make('title')->required()->maxLength(120),
                Textarea::make('text')->rows(2)->maxLength(400),
            ])->minItems(1)->maxItems(12)->collapsible()->itemLabel(fn (array $state): ?string => $state['title'] ?? null)->columnSpanFull(),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'entries' => ['required', 'array', 'min:1', 'max:12'],
            'entries.*.date_label' => ['required', 'string', 'max:60'],
            'entries.*.title' => ['required', 'string', 'max:120'],
            'entries.*.text' => ['nullable', 'string', 'max:400'],
        ];
    }
}
