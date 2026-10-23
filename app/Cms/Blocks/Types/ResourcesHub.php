<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

/**
 * Tabbed teaser of the Resources library: latest blogs, news and social videos. A tab with nothing
 * published is left out, and the whole section disappears when every tab is empty.
 */
class ResourcesHub extends Block
{
    public function key(): string
    {
        return 'resources';
    }

    public function label(): string
    {
        return 'Resources (blogs, news, videos)';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedBookOpen;
    }

    public function hosts(): array
    {
        return ['page', 'landing_page'];
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::intro(),
            Grid::make(3)->schema([
                Toggle::make('show_blog')->label('Blogs tab')->default(true)->inline(false),
                Toggle::make('show_news')->label('News tab')->default(true)->inline(false),
                Toggle::make('show_videos')->label('Videos tab')->default(true)->inline(false),
            ]),
            Fields::limit(3, 8)->label('Items per tab'),
            Grid::make(2)->schema([
                Fields::ctaLabel('more_label', 'Link label'),
                Fields::link('more_url', 'Link to the full Resources page'),
            ]),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'show_blog' => ['nullable', 'boolean'],
            'show_news' => ['nullable', 'boolean'],
            'show_videos' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:8'],
            'more_label' => ['nullable', 'string', 'max:80'],
            'more_url' => Fields::linkRule(),
        ];
    }
}
