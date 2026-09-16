<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Models\Industry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class IndustryGrid extends Block
{
    public function key(): string
    {
        return 'industry_grid';
    }

    public function label(): string
    {
        return 'Industry grid';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedBuildingOffice2;
    }

    public function hosts(): array
    {
        return ['page', 'landing_page', 'service', 'product', 'solution'];
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::intro(),
            Fields::mode(['featured' => 'Featured industries', 'all' => 'All published industries', 'ids' => 'Selected industries'], 'featured'),
            Fields::records('industry_ids', 'Industries', Industry::class)->visible(fn (Get $get): bool => $get('mode') === 'ids'),
            Fields::limit(12),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'mode' => ['required', 'in:featured,all,ids'],
            'industry_ids' => ['required_if:mode,ids', 'nullable', 'array'],
            'industry_ids.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:24'],
        ];
    }
}
