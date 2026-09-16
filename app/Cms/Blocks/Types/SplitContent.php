<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

class SplitContent extends Block
{
    public function key(): string
    {
        return 'split_content';
    }

    public function label(): string
    {
        return 'Split content';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedArrowsRightLeft;
    }

    public function fields(): array
    {
        return [
            Fields::heading(required: true),
            Fields::richText(required: true),
            Repeater::make('bullets')->defaultItems(0)->schema([TextInput::make('text')->required()->maxLength(200)])->simple(TextInput::make('text')->required()->maxLength(200))->maxItems(6)->columnSpanFull(),
            Grid::make(2)->schema([
                Fields::image(),
                Fields::imageAlt(),
                Select::make('media_position')->options(['left' => 'Image left', 'right' => 'Image right'])->default('right')->native(false),
                Fields::cta(),
            ]),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'bullets' => ['nullable', 'array', 'max:6'],
            'bullets.*' => ['string', 'max:200'],
            'image' => Fields::imageRule(),
            'image_alt' => ['nullable', 'string', 'max:255'],
            'media_position' => ['nullable', 'in:left,right'],
            'cta_id' => ['nullable', 'integer'],
        ];
    }
}
