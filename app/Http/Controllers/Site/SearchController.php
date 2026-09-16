<?php

namespace App\Http\Controllers\Site;

use App\Events\SearchPerformed;
use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use App\Models\ServiceCategory;
use App\Search\Contracts\SearchEngine;
use App\Search\QueryNormalizer;
use App\Search\SearchQuery;
use App\Search\SearchResults;
use App\Search\SearchTypes;
use App\Seo\SeoEngine;
use App\Services\Cms\CtaResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * GET /search. Every state (empty, too short, results, no results, backend error) renders the
 * same page; the query string is the only state.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request, SearchEngine $engine, QueryNormalizer $normalizer, SeoEngine $seo, CtaResolver $ctas): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:1000'],
            'type' => ['nullable', 'string', Rule::in(SearchTypes::keys())],
            'category' => ['nullable', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $term = $normalizer->normalise($validated['q'] ?? null);
        $query = new SearchQuery(
            term: $term,
            type: $validated['type'] ?? null,
            category: $validated['category'] ?? null,
            page: (int) ($validated['page'] ?? 1),
        );

        $results = null;
        $failed = false;
        $tooShort = $query->hasTerm() && $normalizer->isTooShort($term);

        if ($query->hasTerm() && ! $tooShort) {
            try {
                $results = $engine->search($query);
                SearchPerformed::dispatch($query, $results->total());
            } catch (Throwable $exception) {
                report($exception);
                $failed = true;
            }
        }

        return view('pages.search', [
            'query' => $query,
            'results' => $results instanceof SearchResults ? $results : null,
            'failed' => $failed,
            'tooShort' => $tooShort,
            'types' => collect(SearchTypes::TYPES)->map(fn (array $definition): string => $definition['plural']),
            'categories' => $this->categories(),
            'meta' => $seo->forListing('Search', 'Search Markedge services, products, solutions, industries and insights.', '/search', indexable: false),
            'cta' => $ctas->fromSetting('cta.default'),
        ]);
    }

    /**
     * Public taxonomies usable as a category filter (service areas and article categories).
     *
     * @return array<string, array<string, string>>
     */
    protected function categories(): array
    {
        return array_filter([
            'Service areas' => ServiceCategory::query()->published()->ordered()->pluck('name', 'slug')->all(),
            'Article categories' => ArticleCategory::query()->visible()->ordered()->pluck('name', 'slug')->all(),
        ]);
    }
}
