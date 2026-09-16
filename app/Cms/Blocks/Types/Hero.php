<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

class Hero extends Block
{
    public function key(): string
    {
        return 'hero';
    }

    public function label(): string
    {
        return 'Hero';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedSparkles;
    }

    public function hosts(): array
    {
        return ['page', 'landing_page'];
    }

    public function fields(): array
    {
        return [
            TextInput::make('eyebrow')->maxLength(120),
            TextInput::make('headline')->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('subheading')->rows(3)->maxLength(600)->columnSpanFull(),
            Grid::make(2)->schema([
                Fields::cta('primary_cta_id', 'Primary call to action'),
                Fields::cta('secondary_cta_id', 'Secondary call to action'),
                Fields::ctaLabel('secondary_label', 'Secondary button label (if no CTA)'),
                Fields::link('secondary_url', 'Secondary button link (if no CTA)'),
            ]),
            Grid::make(2)->schema([
                Select::make('variant')->options(['ecosystem' => 'Technology ecosystem visual', 'image' => 'Image', 'none' => 'Text only'])->default('ecosystem')->native(false),
                Select::make('alignment')->options(['left' => 'Left', 'center' => 'Centre'])->default('left')->native(false),
                Fields::image(),
                Fields::imageAlt(),
            ]),
        ];
    }

    public function rules(): array
    {
        return [
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'headline' => ['required', 'string', 'max:255'],
            'subheading' => ['nullable', 'string', 'max:600'],
            'primary_cta_id' => ['nullable', 'integer'],
            'secondary_cta_id' => ['nullable', 'integer'],
            'secondary_label' => ['nullable', 'string', 'max:80'],
            'secondary_url' => Fields::linkRule(),
            'variant' => ['nullable', 'in:ecosystem,image,none'],
            'alignment' => ['nullable', 'in:left,center'],
            'image' => Fields::imageRule(),
            'image_alt' => ['nullable', 'string', 'max:255'],
        ];
    }
}
