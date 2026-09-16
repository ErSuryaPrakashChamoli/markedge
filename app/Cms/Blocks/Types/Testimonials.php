<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Models\Testimonial;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class Testimonials extends Block
{
    public function key(): string
    {
        return 'testimonials';
    }

    public function label(): string
    {
        return 'Testimonials';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedChatBubbleLeftRight;
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::mode(['for_host' => 'Testimonials linked to this page\'s entity', 'all_visible' => 'All visible testimonials', 'ids' => 'Selected testimonials'], 'all_visible'),
            Fields::records('testimonial_ids', 'Testimonials', Testimonial::class, 'author_name')->visible(fn (Get $get): bool => $get('mode') === 'ids'),
            Fields::limit(3, 9),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', 'in:for_host,all_visible,ids'],
            'testimonial_ids' => ['required_if:mode,ids', 'nullable', 'array'],
            'testimonial_ids.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:9'],
        ];
    }
}
