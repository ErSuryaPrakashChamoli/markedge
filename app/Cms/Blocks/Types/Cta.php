<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class Cta extends Block
{
    public function key(): string
    {
        return 'cta';
    }

    public function label(): string
    {
        return 'Call to action';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedCursorArrowRays;
    }

    public function fields(): array
    {
        return [
            Fields::cta()->required(),
            TextInput::make('heading')->label('Heading override')->maxLength(255),
            Textarea::make('body')->label('Text override')->rows(2)->maxLength(400),
        ];
    }

    public function rules(): array
    {
        return [
            'cta_id' => ['required', 'integer'],
            'heading' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:400'],
        ];
    }
}
