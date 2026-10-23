<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * What an article is, separate from its topic category: long-form blog posts or short news updates.
 */
enum ArticleType: string implements HasColor, HasLabel
{
    case Blog = 'blog';
    case News = 'news';

    public function getLabel(): string
    {
        return match ($this) {
            self::Blog => 'Blog',
            self::News => 'News',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Blog => 'info',
            self::News => 'warning',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Blog => 'Blogs',
            self::News => 'News',
        };
    }
}
