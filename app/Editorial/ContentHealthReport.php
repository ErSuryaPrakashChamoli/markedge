<?php

namespace App\Editorial;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard aggregates over the content inventory. Counts only; no invented score.
 */
class ContentHealthReport
{
    public function __construct(
        private readonly ContentInventoryQuery $inventory,
        private readonly ContentHealthScanner $scanner,
    ) {}

    /**
     * @param  array<int, string>|null  $types
     * @return array<string, int>
     */
    public function counts(?array $types = null): array
    {
        $base = fn (): Builder => $this->inventory->base($types);
        $scan = $this->scanner->results();

        return [
            'awaiting_review' => $base()->where('status', 'review')->count(),
            'scheduled' => $base()->where('status', 'scheduled')->count(),
            'published_recently' => $base()->where('status', 'published')->where('published_at', '>=', now()->subDays(7))->count(),
            'modified_recently' => $base()->where('updated_at', '>=', now()->subDays(7))->count(),
            'expired' => $base()->whereNotNull('unpublish_at')->where('unpublish_at', '<', now())->count(),
            'expiring_soon' => $base()->where('status', 'published')->whereNotNull('unpublish_at')->whereBetween('unpublish_at', [now(), now()->addDays((int) config('markedge.editorial.expiry_reminder_days', 3))])->count(),
            'missing_summary' => $this->missingSummary($base())->count(),
            'missing_image' => $base()->where('has_image', 0)->count(),
            'missing_author' => $base()->where('has_author', 0)->count(),
            'noindex' => $base()->where(fn (Builder $q) => $q->where('robots_index', 0)->orWhere(fn (Builder $inner) => $inner->where('type', 'landing_page')->whereNull('robots_index')))->count(),
            'canonicalized' => $base()->whereNotNull('canonical_url')->where('canonical_url', '!=', '')->count(),
            'unowned' => $base()->whereNull('owner_id')->whereIn('status', ['draft', 'review', 'scheduled', 'published'])->count(),
            'invalid_blocks' => count($scan['invalid_blocks']),
            'broken_links' => count($scan['broken_links']),
            'orphaned_pages' => $this->orphanedPages($scan['link_targets'])->count(),
            'scanned' => $scan['scanned'],
        ];
    }

    /**
     * @param  array<int, string>|null  $types
     * @return Collection<int, object>
     */
    public function list(string $key, ?array $types = null, int $limit = 20): Collection
    {
        $base = $this->inventory->base($types);
        $scan = $this->scanner->results();

        return match ($key) {
            'awaiting_review' => $base->where('status', 'review')->orderBy('submitted_at')->limit($limit)->get(),
            'scheduled' => $base->where('status', 'scheduled')->orderBy('published_at')->limit($limit)->get(),
            'published_recently' => $base->where('status', 'published')->where('published_at', '>=', now()->subDays(7))->orderByDesc('published_at')->limit($limit)->get(),
            'modified_recently' => $base->where('updated_at', '>=', now()->subDays(7))->orderByDesc('updated_at')->limit($limit)->get(),
            'expired' => $base->whereNotNull('unpublish_at')->where('unpublish_at', '<', now())->orderByDesc('unpublish_at')->limit($limit)->get(),
            'missing_summary' => $this->missingSummary($base)->orderByDesc('updated_at')->limit($limit)->get(),
            'missing_image' => $base->where('has_image', 0)->orderByDesc('updated_at')->limit($limit)->get(),
            'missing_author' => $base->where('has_author', 0)->orderByDesc('updated_at')->limit($limit)->get(),
            'invalid_blocks' => collect($scan['invalid_blocks'])->take($limit)->map(fn (array $row) => (object) ($row + ['detail' => $row['message']])),
            'broken_links' => collect($scan['broken_links'])->take($limit)->map(fn (array $row) => (object) ($row + ['detail' => $row['link']])),
            'orphaned_pages' => $this->orphanedPages($scan['link_targets'])->limit($limit)->get(),
            default => collect(),
        };
    }

    protected function missingSummary(Builder $query): Builder
    {
        return $query->where('type', '!=', 'landing_page')
            ->where(fn (Builder $q) => $q->whereNull('summary')->orWhere('summary', ''))
            ->where(fn (Builder $q) => $q->whereNull('seo_description')->orWhere('seo_description', ''));
    }

    /**
     * Published pages that no menu item and no other content links to.
     *
     * @param  array<int, string>  $linkTargets
     */
    protected function orphanedPages(array $linkTargets): Builder
    {
        $linkedSlugs = collect($linkTargets)
            ->filter(fn (string $path): bool => preg_match('/^\/[a-z0-9-]+$/', $path) === 1)
            ->map(fn (string $path): string => ltrim($path, '/'))
            ->values()->all();

        return $this->inventory->base(['page'])
            ->where('status', 'published')
            ->where('slug', '!=', 'home')
            ->whereNotIn('slug', $linkedSlugs ?: [''])
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('menu_items')
                ->where(fn ($inner) => $inner
                    ->where(fn ($m) => $m->where('menu_items.linkable_type', 'page')->whereColumn('menu_items.linkable_id', 'inventory.id'))
                    ->orWhereRaw("menu_items.url = '/' || inventory.slug")))
            ->orderBy('title');
    }

    public function rescan(): void
    {
        $this->scanner->forget();
        DB::disableQueryLog();
    }
}
