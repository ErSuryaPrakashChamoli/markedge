<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class Hero extends Block
{
    /** @var array<string, string> Constrained presets: slides never carry free-form CSS, colours or offsets. */
    public const array SLIDE_SIZES = ['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'];

    /** @var array<string, string> */
    public const array SLIDE_COLOURS = ['white' => 'White', 'dark' => 'Dark', 'brand' => 'Brand orange'];

    /** @var array<string, string> */
    public const array SLIDE_POSITIONS = [
        'top-left' => 'Top left', 'top-center' => 'Top centre', 'top-right' => 'Top right',
        'center-left' => 'Middle left', 'center' => 'Centre', 'center-right' => 'Middle right',
        'bottom-left' => 'Bottom left', 'bottom-center' => 'Bottom centre', 'bottom-right' => 'Bottom right',
    ];

    /** @var array<string, string> */
    public const array SLIDE_BUTTONS = ['primary' => 'Brand button', 'light' => 'White button', 'outline' => 'Outline button'];

    public const int MAX_SLIDES = 8;

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
                Select::make('variant')->options(['ecosystem' => 'Technology ecosystem visual', 'image' => 'Image', 'carousel' => 'Slide carousel (60 / 40 split)', 'none' => 'Text only'])->default('ecosystem')->native(false)->live(),
                Select::make('alignment')->options(['left' => 'Left', 'center' => 'Centre'])->default('left')->native(false),
                Fields::image()->visible(fn ($get) => $get('variant') === 'image'),
                Fields::imageAlt()->visible(fn ($get) => $get('variant') === 'image'),
            ]),
            Section::make('Carousel')
                ->description('Right-hand column (40% on desktop). Each slide has its own picture, text, button and placement presets.')
                ->visible(fn ($get) => $get('variant') === 'carousel')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('autoplay_seconds')->label('Autoplay')->options([0 => 'Off', 4 => 'Every 4 seconds', 6 => 'Every 6 seconds', 8 => 'Every 8 seconds', 10 => 'Every 10 seconds'])->default(6)->native(false),
                        Select::make('slide_ratio')->label('Slide shape')->options(['portrait' => 'Portrait (4:5)', 'square' => 'Square', 'landscape' => 'Landscape (4:3)'])->default('portrait')->native(false),
                    ]),
                    Repeater::make('slides')->defaultItems(0)->maxItems(self::MAX_SLIDES)->collapsible()->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['heading'] ?? $state['image_alt'] ?? null)
                        ->schema([
                            Fields::image('image', 'Slide picture')->directory('blocks/hero')->required()->columnSpanFull(),
                            Fields::imageAlt()->required(),
                            TextInput::make('heading')->maxLength(120),
                            TextInput::make('subheading')->maxLength(200)->columnSpanFull(),
                            Grid::make(2)->schema([
                                Fields::ctaLabel('button_label', 'Button label'),
                                Fields::link('button_url', 'Button link'),
                            ]),
                            Grid::make(4)->schema([
                                Select::make('text_size')->label('Text size')->options(self::SLIDE_SIZES)->default('md')->native(false),
                                Select::make('text_color')->label('Text colour')->options(self::SLIDE_COLOURS)->default('white')->native(false),
                                Select::make('text_position')->label('Text position')->options(self::SLIDE_POSITIONS)->default('bottom-left')->native(false),
                                Select::make('button_style')->label('Button style')->options(self::SLIDE_BUTTONS)->default('primary')->native(false),
                            ]),
                        ])->columns(2)->columnSpanFull(),
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
            'variant' => ['nullable', 'in:ecosystem,image,carousel,none'],
            'alignment' => ['nullable', 'in:left,center'],
            'image' => Fields::imageRule(),
            'image_alt' => ['nullable', 'string', 'max:255'],
            'autoplay_seconds' => ['nullable', 'integer', 'in:0,4,6,8,10'],
            'slide_ratio' => ['nullable', 'in:portrait,square,landscape'],
            'slides' => ['nullable', 'array', 'max:'.self::MAX_SLIDES],
            'slides.*.image' => ['required', 'string', 'max:500'],
            'slides.*.image_alt' => ['required', 'string', 'max:255'],
            'slides.*.heading' => ['nullable', 'string', 'max:120'],
            'slides.*.subheading' => ['nullable', 'string', 'max:200'],
            'slides.*.button_label' => ['nullable', 'string', 'max:80'],
            'slides.*.button_url' => Fields::linkRule(),
            'slides.*.text_size' => ['nullable', 'in:'.implode(',', array_keys(self::SLIDE_SIZES))],
            'slides.*.text_color' => ['nullable', 'in:'.implode(',', array_keys(self::SLIDE_COLOURS))],
            'slides.*.text_position' => ['nullable', 'in:'.implode(',', array_keys(self::SLIDE_POSITIONS))],
            'slides.*.button_style' => ['nullable', 'in:'.implode(',', array_keys(self::SLIDE_BUTTONS))],
        ];
    }
}
