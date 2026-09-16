<?php

namespace App\Search\Engines;

use App\Models\SearchEntry;
use App\Search\Contracts\SearchEngine;
use App\Search\QueryNormalizer;
use App\Search\SearchDocument;
use App\Search\SearchQuery;
use App\Search\SearchResult;
use App\Search\SearchResults;
use App\Search\SearchTypes;
use App\Seo\IndexabilityResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Search over the search_entries table. MySQL uses the full-text index in boolean mode;
 * other drivers (the SQLite test suite) fall back to bounded LIKE matching. Ranking is the
 * same deterministic expression on both: exact title, title prefix, title contains, summary,
 * keywords, then the content-type weight and recency.
 */
class DatabaseSearchEngine implements SearchEngine
{
    public function __construct(
        private readonly QueryNormalizer $normalizer,
        private readonly IndexabilityResolver $urls,
    ) {}

    public function search(SearchQuery $query): SearchResults
    {
        $tokens = $this->normalizer->tokens($query->term);

        if ($tokens === []) {
            return new SearchResults($query, SearchEntry::query()->whereRaw('1 = 0')->paginate($query->perPage)->withQueryString());
        }

        $paginator = $this->run($query, $tokens, requireAll: true);
        $fallback = false;

        if ($paginator->total() === 0 && count($tokens) > 1) {
            $paginator = $this->run($query, $tokens, requireAll: false);
            $fallback = true;
        }

        $paginator->through(fn (SearchEntry $entry): SearchResult => new SearchResult(
            type: $entry->kind,
            typeLabel: SearchTypes::label($entry->kind),
            title: $entry->title,
            excerpt: $entry->summary ? mb_strimwidth($entry->summary, 0, 200, '…') : null,
            url: $this->urls->absolute($entry->url),
            context: $entry->category_label,
            publishedAt: $entry->published_at,
        ));

        return new SearchResults($query, $paginator, $fallback);
    }

    /**
     * @param  array<int, string>  $tokens
     * @return LengthAwarePaginator<int, SearchEntry>
     */
    protected function run(SearchQuery $query, array $tokens, bool $requireAll)
    {
        $builder = SearchEntry::query()
            ->when($query->type, fn (Builder $q, string $type) => $q->where('kind', $type))
            ->when($query->category, fn (Builder $q, string $category) => $q->where('category_slug', $category));

        $this->applyMatch($builder, $tokens, $requireAll);
        $this->applyRanking($builder, $tokens);

        return $builder->paginate($query->perPage, ['*'], 'page', $query->page)->withQueryString();
    }

    /**
     * @param  Builder<SearchEntry>  $builder
     * @param  array<int, string>  $tokens
     */
    protected function applyMatch(Builder $builder, array $tokens, bool $requireAll): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            // Tokens shorter than the InnoDB minimum token size never match full text; they use title LIKE instead.
            $fullText = array_filter($tokens, fn (string $token): bool => mb_strlen($token) >= 3);
            $short = array_diff($tokens, $fullText);

            $builder->where(function (Builder $q) use ($fullText, $short, $requireAll): void {
                if ($fullText !== []) {
                    $expression = implode(' ', array_map(fn (string $token): string => ($requireAll ? '+' : '').$this->escapeBoolean($token).'*', $fullText));
                    $q->whereRaw('MATCH(title, summary, body_text, keywords) AGAINST (? IN BOOLEAN MODE)', [$expression]);
                }

                foreach ($short as $token) {
                    $method = $requireAll || $fullText === [] ? 'where' : 'orWhere';
                    $q->{$method}('title', 'like', '%'.$this->escapeLike($token).'%');
                }
            });

            return;
        }

        $builder->where(function (Builder $q) use ($tokens, $requireAll): void {
            foreach ($tokens as $index => $token) {
                $like = '%'.$this->escapeLike($token).'%';
                $group = fn (Builder $g) => $g->where('title', 'like', $like)->orWhere('summary', 'like', $like)->orWhere('body_text', 'like', $like)->orWhere('keywords', 'like', $like);

                if ($requireAll || $index === 0) {
                    $q->where($group);
                } else {
                    $q->orWhere($group);
                }
            }
        });
    }

    /**
     * @param  Builder<SearchEntry>  $builder
     * @param  array<int, string>  $tokens
     */
    protected function applyRanking(Builder $builder, array $tokens): void
    {
        $phrase = implode(' ', $tokens);
        $like = '%'.$this->escapeLike($phrase).'%';
        $first = '%'.$this->escapeLike($tokens[0]).'%';

        $builder->select('search_entries.*')->selectRaw(
            '(CASE WHEN LOWER(title) = ? THEN 400 WHEN LOWER(title) LIKE ? THEN 250 WHEN LOWER(title) LIKE ? THEN 150 ELSE 0 END)'
            .' + (CASE WHEN LOWER(summary) LIKE ? THEN 60 ELSE 0 END)'
            .' + (CASE WHEN LOWER(keywords) LIKE ? THEN 40 ELSE 0 END)'
            .' + weight AS relevance',
            [$phrase, $this->escapeLike($phrase).'%', $like, $like, $first],
        )->orderByDesc('relevance')->orderByDesc('published_at')->orderByDesc('id');
    }

    public function index(SearchDocument $document): void
    {
        SearchEntry::query()->updateOrCreate(
            ['searchable_type' => $document->type, 'searchable_id' => $document->id],
            [
                'kind' => $document->type,
                'category_slug' => $document->categorySlug,
                'category_label' => $document->categoryLabel,
                'title' => $document->title,
                'summary' => $document->summary,
                'body_text' => $document->body,
                'keywords' => $document->keywords === [] ? null : implode(' ', $document->keywords),
                'weight' => $document->weight,
                'url' => $document->path,
                'published_at' => $document->publishedAt,
            ],
        );
    }

    public function remove(string $type, int $id): void
    {
        SearchEntry::query()->where('searchable_type', $type)->where('searchable_id', $id)->delete();
    }

    public function removeAll(): void
    {
        SearchEntry::query()->delete();
    }

    public function stats(): array
    {
        return [
            'total' => SearchEntry::query()->count(),
            'by_type' => SearchEntry::query()->selectRaw('kind, COUNT(*) as aggregate')->groupBy('kind')->pluck('aggregate', 'kind')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function escapeBoolean(string $token): string
    {
        return preg_replace('/[+\-<>()~*"@]/u', '', $token) ?? '';
    }

    protected function escapeLike(string $value): string
    {
        return addcslashes(mb_strtolower($value), '%_\\');
    }
}
