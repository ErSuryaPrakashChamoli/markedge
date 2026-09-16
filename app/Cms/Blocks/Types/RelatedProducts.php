<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Models\Product;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class RelatedProducts extends Block
{
    public function key(): string
    {
        return 'related_products';
    }

    public function label(): string
    {
        return 'Related products';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedCube;
    }

    public function hosts(): array
    {
        return ['service', 'product', 'solution', 'industry', 'case_study', 'page'];
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::mode(['auto' => 'Automatic from relationships', 'ids' => 'Selected products'], 'auto'),
            Fields::records('product_ids', 'Products', Product::class)->visible(fn (Get $get): bool => $get('mode') === 'ids'),
            Fields::limit(3, 6),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', 'in:auto,ids'],
            'product_ids' => ['required_if:mode,ids', 'nullable', 'array'],
            'product_ids.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:6'],
        ];
    }
}
