<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Models\Solution;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class SolutionGrid extends Block
{
    public function key(): string
    {
        return 'solution_grid';
    }

    public function label(): string
    {
        return 'Solution grid';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedLightBulb;
    }

    public function hosts(): array
    {
        return ['page', 'landing_page', 'industry', 'service', 'product'];
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::intro(),
            Fields::mode(['featured' => 'Featured solutions', 'all' => 'All published solutions', 'ids' => 'Selected solutions'], 'featured'),
            Fields::records('solution_ids', 'Solutions', Solution::class)->visible(fn (Get $get): bool => $get('mode') === 'ids'),
            Fields::limit(8),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'mode' => ['required', 'in:featured,all,ids'],
            'solution_ids' => ['required_if:mode,ids', 'nullable', 'array'],
            'solution_ids.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:24'],
        ];
    }
}
