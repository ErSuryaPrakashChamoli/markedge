<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class ImageContent extends Block
{
    public function key(): string
    {
        return 'image_content';
    }

    public function label(): string
    {
        return 'Image with caption';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedPhoto;
    }

    public function fields(): array
    {
        return [
            Fields::image()->required(),
            Fields::imageAlt()->required(),
            TextInput::make('caption')->maxLength(255),
            Fields::heading(),
            Fields::richText(),
            Fields::layout(['full' => 'Full width', 'contained' => 'Contained'], 'contained'),
        ];
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'string', 'max:500'],
            'image_alt' => ['required', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'heading' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'layout' => ['nullable', 'in:full,contained'],
        ];
    }
}
