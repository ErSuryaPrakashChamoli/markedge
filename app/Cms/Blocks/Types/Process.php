<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class Process extends Block
{
    public function key(): string
    {
        return 'process';
    }

    public function label(): string
    {
        return 'Process steps';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedListBullet;
    }

    public function fields(): array
    {
        return [
            Fields::heading(required: true),
            Fields::intro(),
            Repeater::make('steps')->schema([
                TextInput::make('title')->required()->maxLength(80),
                Textarea::make('text')->rows(2)->maxLength(400),
            ])->minItems(2)->maxItems(8)->collapsible()->itemLabel(fn (array $state): ?string => $state['title'] ?? null)->columnSpanFull(),
            Fields::layout(['horizontal' => 'Horizontal stepper', 'vertical' => 'Vertical stepper'], 'horizontal', 'orientation'),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['required', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'steps' => ['required', 'array', 'min:2', 'max:8'],
            'steps.*.title' => ['required', 'string', 'max:80'],
            'steps.*.text' => ['nullable', 'string', 'max:400'],
            'orientation' => ['nullable', 'in:horizontal,vertical'],
        ];
    }
}
