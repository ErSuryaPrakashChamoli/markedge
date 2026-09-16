<?php

namespace App\Editorial;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * One SQL view over every workflow content type (UNION ALL of the real tables), so the review
 * queue, inventory and health counts aggregate in the database instead of loading models.
 */
class ContentInventoryQuery
{
    /** @var array<string, array{table: string, title: string, summary: string, image: ?string, category: ?array{table: string, key: string}, author: ?string}> */
    public const array TYPES = [
        'page' => ['table' => 'pages', 'title' => 'title', 'summary' => 'excerpt', 'image' => null, 'category' => null, 'author' => null],
        'service_category' => ['table' => 'service_categories', 'title' => 'name', 'summary' => 'short_description', 'image' => 'hero', 'category' => null, 'author' => null],
        'service' => ['table' => 'services', 'title' => 'name', 'summary' => 'short_description', 'image' => null, 'category' => ['table' => 'service_categories', 'key' => 'service_category_id'], 'author' => null],
        'solution' => ['table' => 'solutions', 'title' => 'name', 'summary' => 'short_description', 'image' => null, 'category' => null, 'author' => null],
        'industry' => ['table' => 'industries', 'title' => 'name', 'summary' => 'short_description', 'image' => null, 'category' => null, 'author' => null],
        'case_study' => ['table' => 'case_studies', 'title' => 'title', 'summary' => 'excerpt', 'image' => 'hero', 'category' => null, 'author' => null],
        'article' => ['table' => 'articles', 'title' => 'title', 'summary' => 'excerpt', 'image' => 'featured', 'category' => ['table' => 'article_categories', 'key' => 'article_category_id'], 'author' => 'author_id'],
        'landing_page' => ['table' => 'landing_pages', 'title' => 'title', 'summary' => null, 'image' => null, 'category' => null, 'author' => null],
    ];

    /**
     * @param  array<int, string>|null  $types  restrict to these aliases (e.g. the ones the user may view)
     */
    public function base(?array $types = null): Builder
    {
        $union = null;

        foreach (self::TYPES as $alias => $definition) {
            if ($types !== null && ! in_array($alias, $types, true)) {
                continue;
            }

            $member = $this->member($alias, $definition);
            $union = $union === null ? $member : $union->unionAll($member);
        }

        if ($union === null) {
            $union = DB::query()->selectRaw("'none' as type, 0 as id, '' as title, '' as slug, '' as status, NULL as published_at, NULL as unpublish_at, NULL as updated_at, NULL as created_at, NULL as owner_id, NULL as reviewer_id, NULL as created_by, NULL as submitted_at, NULL as approved_at, NULL as category, NULL as summary, NULL as seo_description, NULL as robots_index, NULL as canonical_url, 1 as has_image, 1 as has_author")->whereRaw('1 = 0');
        }

        return DB::query()->fromSub($union, 'inventory');
    }

    /**
     * @param  array{table: string, title: string, summary: ?string, image: ?string, category: ?array{table: string, key: string}, author: ?string}  $definition
     */
    protected function member(string $alias, array $definition): Builder
    {
        $t = $definition['table'];
        $summary = $definition['summary'] ? "{$t}.{$definition['summary']}" : 'NULL';
        $category = $definition['category'] ? 'cat.slug' : 'NULL';
        $hasImage = $definition['image']
            ? "EXISTS (SELECT 1 FROM media WHERE media.model_type = '{$alias}' AND media.model_id = {$t}.id AND media.collection_name = '{$definition['image']}')"
            : '1';
        $hasAuthor = $definition['author'] ? "CASE WHEN {$t}.{$definition['author']} IS NULL THEN 0 ELSE 1 END" : '1';

        $query = DB::table($t)
            ->leftJoin('seo_meta as seo', function ($join) use ($alias, $t): void {
                $join->on('seo.seoable_id', '=', "{$t}.id")->where('seo.seoable_type', '=', $alias);
            })
            ->whereNull("{$t}.deleted_at")
            ->selectRaw("'{$alias}' as type, {$t}.id, {$t}.{$definition['title']} as title, {$t}.slug, {$t}.status, {$t}.published_at, {$t}.unpublish_at, {$t}.updated_at, {$t}.created_at, {$t}.owner_id, {$t}.reviewer_id, {$t}.created_by, {$t}.submitted_at, {$t}.approved_at, {$category} as category, {$summary} as summary, seo.description as seo_description, seo.robots_index, seo.canonical_url, {$hasImage} as has_image, {$hasAuthor} as has_author");

        if ($definition['category']) {
            $query->leftJoin("{$definition['category']['table']} as cat", 'cat.id', '=', "{$t}.{$definition['category']['key']}");
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>|null  $types
     */
    public function paginate(array $filters, ?array $types = null, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        $query = $this->base($types);

        $query->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when(array_filter((array) ($filters['status'] ?? [])), fn (Builder $q, array $statuses) => $q->whereIn('status', $statuses))
            ->when($filters['owner'] ?? null, fn (Builder $q, $id) => $q->where('owner_id', (int) $id))
            ->when($filters['reviewer'] ?? null, fn (Builder $q, $id) => $q->where('reviewer_id', (int) $id))
            ->when($filters['author'] ?? null, fn (Builder $q, $id) => $q->where('created_by', (int) $id))
            ->when($filters['category'] ?? null, fn (Builder $q, string $slug) => $q->where('category', $slug))
            ->when($filters['search'] ?? null, fn (Builder $q, string $term) => $q->where('title', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->when($filters['published_from'] ?? null, fn (Builder $q, string $date) => $q->where('published_at', '>=', $date))
            ->when($filters['published_until'] ?? null, fn (Builder $q, string $date) => $q->where('published_at', '<', date('Y-m-d', strtotime($date.' +1 day'))))
            ->when($filters['updated_from'] ?? null, fn (Builder $q, string $date) => $q->where('updated_at', '>=', $date))
            ->when($filters['updated_until'] ?? null, fn (Builder $q, string $date) => $q->where('updated_at', '<', date('Y-m-d', strtotime($date.' +1 day'))))
            ->when($filters['expired'] ?? false, fn (Builder $q) => $q->whereNotNull('unpublish_at')->where('unpublish_at', '<', now()));

        match ($filters['indexability'] ?? null) {
            'noindex' => $query->where(fn (Builder $q) => $q->where('robots_index', 0)->orWhere(fn (Builder $inner) => $inner->where('type', 'landing_page')->whereNull('robots_index'))),
            'canonicalized' => $query->whereNotNull('canonical_url')->where('canonical_url', '!=', ''),
            'indexable' => $query->where('status', 'published')->where(fn (Builder $q) => $q->where('robots_index', 1)->orWhere(fn (Builder $inner) => $inner->whereNull('robots_index')->where('type', '!=', 'landing_page')))->where(fn (Builder $q) => $q->whereNull('canonical_url')->orWhere('canonical_url', '')),
            default => null,
        };

        $sort = in_array($filters['sort'] ?? null, ['updated_at', 'published_at', 'submitted_at', 'title', 'unpublish_at'], true) ? $filters['sort'] : 'updated_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('id', 'desc')->paginate(min(100, max(5, $perPage)), ['*'], 'page', max(1, $page));
    }
}
