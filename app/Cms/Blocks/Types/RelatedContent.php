<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Support\Icons\Heroicon;

/**
 * Automatic related content drawn from the host entity's relationships (architecture §17).
 */
class RelatedContent extends Block
{
    public function key(): string
    {
        return 'related_content';
    }

    public function label(): string
    {
        return 'Related content';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedLink;
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::limit(6, 12),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
