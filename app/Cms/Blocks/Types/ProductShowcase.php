<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Models\Product;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ProductShowcase extends Block
{
    public function key(): string
    {
        return 'product_showcase';
    }

    public function label(): string
    {
        return 'Product showcase';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedCube;
    }

    public function hosts(): array
    {
        return ['page', 'landing_page', 'solution', 'industry', 'service'];
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::intro(),
            Fields::mode(['all_active' => 'All visible products', 'ids' => 'Selected products'], 'all_active'),
            Fields::records('product_ids', 'Products', Product::class)->visible(fn (Get $get): bool => $get('mode') === 'ids'),
            Fields::layout(['alternating' => 'Alternating rows', 'grid' => 'Grid'], 'alternating'),
            Fields::ctaLabel(),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'mode' => ['required', 'in:all_active,ids'],
            'product_ids' => ['required_if:mode,ids', 'nullable', 'array'],
            'product_ids.*' => ['integer'],
            'layout' => ['nullable', 'in:alternating,grid'],
            'cta_label' => ['nullable', 'string', 'max:80'],
        ];
    }
}
