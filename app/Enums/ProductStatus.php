<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Active = 'active';
    case ComingSoon = 'coming_soon';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::ComingSoon => 'Coming soon',
            self::Archived => 'Archived',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Active => 'success',
            self::ComingSoon => 'info',
            self::Archived => 'danger',
        };
    }

    /**
     * Statuses that render a public product page.
     *
     * @return array<int, self>
     */
    public static function publiclyVisible(): array
    {
        return [self::Active, self::ComingSoon];
    }
}
