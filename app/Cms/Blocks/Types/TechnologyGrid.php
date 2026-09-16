<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Enums\TechnologyCategory;
use App\Models\Technology;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class TechnologyGrid extends Block
{
    public function key(): string
    {
        return 'technology_grid';
    }

    public function label(): string
    {
        return 'Technology grid';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedCpuChip;
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::intro(),
            Fields::mode(['all' => 'All visible technologies', 'categories' => 'Selected categories', 'ids' => 'Selected technologies'], 'all'),
            Select::make('category_keys')->label('Categories')->multiple()->options(TechnologyCategory::class)->visible(fn (Get $get): bool => $get('mode') === 'categories'),
            Fields::records('technology_ids', 'Technologies', Technology::class)->visible(fn (Get $get): bool => $get('mode') === 'ids'),
            Fields::layout(['logos' => 'Logos', 'list' => 'Grouped list'], 'logos', 'display'),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'mode' => ['required', 'in:all,categories,ids'],
            'category_keys' => ['required_if:mode,categories', 'nullable', 'array'],
            'category_keys.*' => ['in:'.implode(',', array_column(TechnologyCategory::cases(), 'value'))],
            'technology_ids' => ['required_if:mode,ids', 'nullable', 'array'],
            'technology_ids.*' => ['integer'],
            'display' => ['nullable', 'in:logos,list'],
        ];
    }
}
