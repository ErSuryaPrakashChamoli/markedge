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
            $this->blocks ?? [],
            fn (array $block): bool => ($block['data']['is_enabled'] ?? true) !== false,
        ));
    }
}
