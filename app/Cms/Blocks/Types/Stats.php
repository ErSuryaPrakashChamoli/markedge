<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

/**
 * Figures render exactly as entered. The attestation toggle records that the editor
 * confirmed the numbers are genuine (architecture §2.3).
 */
class Stats extends Block
{
    public function key(): string
    {
        return 'stats';
    }

    public function label(): string
    {
        return 'Statistics';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedPresentationChartLine;
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Repeater::make('items')->schema([
                TextInput::make('value')->required()->maxLength(40),
                TextInput::make('label')->required()->maxLength(120),
                TextInput::make('note')->maxLength(160),
            ])->minItems(1)->maxItems(6)->columns(3)->columnSpanFull(),
            TextInput::make('source_note')->label('Source or measurement note')->maxLength(255)->columnSpanFull(),
            Toggle::make('attested')->label('I confirm these figures are genuine and verifiable')->required()->accepted(),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:6'],
            'items.*.value' => ['required', 'string', 'max:40'],
            'items.*.label' => ['required', 'string', 'max:120'],
            'items.*.note' => ['nullable', 'string', 'max:160'],
            'source_note' => ['nullable', 'string', 'max:255'],
            'attested' => ['accepted'],
        ];
    }
}
