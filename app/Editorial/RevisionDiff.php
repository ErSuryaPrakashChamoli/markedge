<?php

namespace App\Editorial;

use App\Cms\Blocks\BlockRegistry;
use App\Models\ContentRevision;

/**
 * Human-readable differences between two snapshots: fields, SEO, blocks, media and relations.
 */
class RevisionDiff
{
    public function __construct(private readonly BlockRegistry $blocks) {}

    /**
     * @return array<int, array{section: string, field: string, from: string, to: string}>
     */
    public function between(ContentRevision $from, ContentRevision $to): array
    {
        return $this->betweenSnapshots($from->snapshot, $to->snapshot);
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     * @return array<int, array{section: string, field: string, from: string, to: string}>
     */
    public function betweenSnapshots(array $a, array $b): array
    {
        $changes = [];

        $keys = array_unique(array_merge(array_keys($a['attributes'] ?? []), array_keys($b['attributes'] ?? [])));

        foreach ($keys as $key) {
            $before = $a['attributes'][$key] ?? null;
            $after = $b['attributes'][$key] ?? null;

            if ($before === $after) {
                continue;
            }

            if ($key === 'blocks') {
                array_push($changes, ...$this->blockChanges(is_array($before) ? $before : [], is_array($after) ? $after : []));

                continue;
            }

            $changes[] = ['section' => 'Content', 'field' => $this->label($key), 'from' => $this->text($before), 'to' => $this->text($after)];
        }

        foreach (array_unique(array_merge(array_keys($a['seo'] ?? []), array_keys($b['seo'] ?? []))) as $key) {
            $before = $a['seo'][$key] ?? null;
            $after = $b['seo'][$key] ?? null;

            if ($before !== $after) {
                $changes[] = ['section' => 'SEO', 'field' => $this->label($key), 'from' => $this->text($before), 'to' => $this->text($after)];
            }
        }

        foreach (['media' => 'Media', 'relations' => 'Relations'] as $group => $section) {
            foreach (array_unique(array_merge(array_keys($a[$group] ?? []), array_keys($b[$group] ?? []))) as $name) {
                $before = $a[$group][$name] ?? [];
                $after = $b[$group][$name] ?? [];

                if ($before !== $after) {
                    $changes[] = ['section' => $section, 'field' => $this->label($name), 'from' => count($before).' item(s): '.implode(', ', $before), 'to' => count($after).' item(s): '.implode(', ', $after)];
                }
            }
        }

        return $changes;
    }

    /**
     * @param  array<int, mixed>  $before
     * @param  array<int, mixed>  $after
     * @return array<int, array{section: string, field: string, from: string, to: string}>
     */
    protected function blockChanges(array $before, array $after): array
    {
        $changes = [];
        $max = max(count($before), count($after));

        for ($i = 0; $i < $max; $i++) {
            $a = $before[$i] ?? null;
            $b = $after[$i] ?? null;
            $position = 'Block '.($i + 1);

            if ($a === $b) {
                continue;
            }

            if ($a === null) {
                $changes[] = ['section' => 'Blocks', 'field' => $position, 'from' => '—', 'to' => 'added '.$this->blockLabel($b)];
            } elseif ($b === null) {
                $changes[] = ['section' => 'Blocks', 'field' => $position, 'from' => $this->blockLabel($a), 'to' => 'removed'];
            } elseif (($a['type'] ?? null) !== ($b['type'] ?? null)) {
                $changes[] = ['section' => 'Blocks', 'field' => $position, 'from' => $this->blockLabel($a), 'to' => $this->blockLabel($b)];
            } else {
                $fields = [];

                foreach (array_unique(array_merge(array_keys($a['data'] ?? []), array_keys($b['data'] ?? []))) as $field) {
                    if (($a['data'][$field] ?? null) !== ($b['data'][$field] ?? null)) {
                        $fields[] = $this->label($field);
                    }
                }

                $changes[] = ['section' => 'Blocks', 'field' => $position.' ('.$this->blockLabel($a).')', 'from' => 'changed: '.implode(', ', $fields), 'to' => ''];
            }
        }

        return $changes;
    }

    protected function blockLabel(mixed $block): string
    {
        $type = is_array($block) ? (string) ($block['type'] ?? 'unknown') : 'unknown';

        return $this->blocks->find($type)?->label() ?? $type;
    }

    protected function label(string $key): string
    {
        return ucfirst(str_replace('_', ' ', $key));
    }

    protected function text(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (is_array($value)) {
            return mb_strimwidth(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 160, '…');
        }

        return mb_strimwidth(trim(strip_tags((string) $value)), 0, 160, '…');
    }
}
