<?php

namespace App\Filament\Support;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\BlockRegistry;
use Filament\Forms\Components\Builder;

/**
 * The typed block editor. Only registry blocks allowed on the host appear in the picker.
 * Field rules validate on save; the full contract is re-checked by the Publisher before publishing.
 */
class BlockBuilder
{
    public static function make(string $host): Builder
    {
        $registry = app(BlockRegistry::class);

        return Builder::make('blocks')
            ->label('Sections')
            ->blocks(array_values(array_map(fn (Block $block) => $block->toFilamentBlock(), $registry->forHost($host))))
            ->addActionLabel('Add section')
            ->blockNumbers(false)
            ->blockIcons()
            ->blockPickerColumns(3)
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->reorderableWithButtons()
            ->columnSpanFull();
    }
}
