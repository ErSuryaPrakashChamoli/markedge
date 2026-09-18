<?php

namespace App\Cms\Blocks;

use Illuminate\Database\Eloquent\Model;

/**
 * Stored JSON → whitelist → hydrate → render-ready list. Unknown types are dropped
 * (or labelled in preview) and never reach a Blade component (architecture §6.2, §10).
 *
 * @phpstan-type PreparedBlock array{key: string, component: string, data: array<string, mixed>, label: string, unknown?: bool}
 */
class BlockRenderer
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly BlockHydrator $hydrator,
    ) {}

    /**
     * @param  array<int, mixed>  $blocks
     * @return array<int, array{key: string, component: string, data: array<string, mixed>, label: string, unknown?: bool}>
     */
    public function prepare(array $blocks, ?Model $host = null, bool $preview = false): array
    {
        $prepared = [];
        $hostKey = $host?->getMorphClass();
        $previousTheme = 'dark';

        foreach ($blocks as $index => $entry) {
            $type = is_array($entry) && is_string($entry['type'] ?? null) ? $entry['type'] : null;
            $block = $type ? $this->registry->find($type) : null;

            if ($block === null || ($hostKey && ! $block->allowedOn($hostKey))) {
                if ($preview) {
                    $prepared[] = ['key' => $type ?? 'unknown', 'component' => 'blocks.unknown', 'data' => ['label' => $type ?? 'malformed block', 'position' => $index + 1], 'label' => 'Unknown block', 'unknown' => true];
                }

                continue;
            }

            $data = is_array($entry['data'] ?? null) ? $entry['data'] : [];

            if (($data['is_enabled'] ?? true) === false) {
                continue;
            }

            $hydrated = $this->hydrator->hydrate($block, $data, $host);

            if ($hydrated === null) {
                continue;
            }

            $theme = $this->resolveTheme($data['theme'] ?? null, $previousTheme);
            $previousTheme = $theme;

            $prepared[] = [
                'key' => $block->key(),
                'component' => 'blocks.'.str_replace('_', '-', $block->key()),
                'data' => array_merge($hydrated, ['theme' => $theme, 'anchor' => $data['anchor'] ?? null]),
                'label' => $block->label(),
            ];
        }

        return $prepared;
    }

    /**
     * Dark and neutral are deliberate editor choices and are kept as-is. The default light
     * theme alternates with neutral so consecutive sections never share the same background.
     */
    private function resolveTheme(mixed $stored, string $previousTheme): string
    {
        if (in_array($stored, ['dark', 'neutral'], true)) {
            return $stored;
        }

        return $previousTheme === 'light' ? 'neutral' : 'light';
    }
}
