<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Models\Article;
use App\Models\ArticleCategory;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ArticleGrid extends Block
{
    public function key(): string
    {
        return 'article_grid';
    }

    public function label(): string
    {
        return 'Article grid';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedNewspaper;
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::intro(),
            Fields::mode(['latest' => 'Latest published', 'category' => 'Latest in a category', 'ids' => 'Selected articles'], 'latest'),
            Select::make('article_category_id')->label('Category')->options(fn () => ArticleCategory::query()->ordered()->pluck('name', 'id'))->native(false)
                ->visible(fn (Get $get): bool => $get('mode') === 'category')->required(fn (Get $get): bool => $get('mode') === 'category'),
            Fields::records('article_ids', 'Articles', Article::class, 'title')->visible(fn (Get $get): bool => $get('mode') === 'ids'),
            Fields::limit(3, 12),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'mode' => ['required', 'in:latest,category,ids'],
            'article_category_id' => ['required_if:mode,category', 'nullable', 'integer'],
            'article_ids' => ['required_if:mode,ids', 'nullable', 'array'],
            'article_ids.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
