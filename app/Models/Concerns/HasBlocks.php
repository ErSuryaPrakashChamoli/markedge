<?php

namespace App\Models\Concerns;

/**
 * Hosts a typed block tree (Filament Builder format) in the `blocks` JSON column.
 * Block schemas and rendering live in App\Cms\Blocks (Phase 4/5).
 */
trait HasBlocks
{
    public function initializeHasBlocks(): void
    {
        $this->mergeCasts(['blocks' => 'array']);
    }

    /**
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    public function enabledBlocks(): array
    {
        return array_values(array_filter(
            is_array($this->blocks) ? $this->blocks : [],
            fn (mixed $block): bool => is_array($block) && ($block['data']['is_enabled'] ?? true) !== false,
        ));
    }
}
