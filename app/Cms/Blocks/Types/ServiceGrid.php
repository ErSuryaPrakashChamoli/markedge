<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Models\Service;
use App\Models\ServiceCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ServiceGrid extends Block
{
    public function key(): string
    {
        return 'service_grid';
    }

    public function label(): string
    {
        return 'Service grid';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedWrenchScrewdriver;
    }

    public function hosts(): array
    {
        return ['page', 'landing_page', 'solution', 'industry', 'product'];
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::intro(),
            Fields::mode(['category' => 'All services in a category', 'ids' => 'Selected services'], 'category'),
            Select::make('service_category_id')->label('Service category')
                ->options(fn () => ServiceCategory::query()->ordered()->pluck('name', 'id'))
                ->native(false)
                ->visible(fn (Get $get): bool => $get('mode') === 'category')
                ->required(fn (Get $get): bool => $get('mode') === 'category'),
            Fields::records('service_ids', 'Services', Service::class)->visible(fn (Get $get): bool => $get('mode') === 'ids'),
            Fields::limit(),
            Fields::layout(['grid' => 'Grid', 'list' => 'List'], 'grid'),
            Toggle::make('show_category_cta')->label('Show link to the category page')->default(true),
            Fields::ctaLabel(),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'mode' => ['required', 'in:category,ids'],
            'service_category_id' => ['required_if:mode,category', 'nullable', 'integer'],
            'service_ids' => ['required_if:mode,ids', 'nullable', 'array'],
            'service_ids.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:24'],
            'layout' => ['nullable', 'in:grid,list'],
            'show_category_cta' => ['sometimes', 'boolean'],
            'cta_label' => ['nullable', 'string', 'max:80'],
        ];
    }
}
