<?php

namespace App\Services\Cms;

use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\Service;
use App\Models\Solution;
use App\Seo\IndexabilityResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Related content from real signals, ranked deterministically (architecture §17.2, Phase 7 §30–35).
 *
 * Weights (documented, tested, never shown to visitors):
 *   explicit CMS relationship   100 (+ ordering bonus so admin order wins ties)
 *   derived relationship         80 (case studies via a solution's services/products)
 *   shared industry              25
 *   same category                30 (service category, article category)
 *   shared tag                   20 per tag, capped at 60
 *   featured product fallback     5 (documented weaker fallback, products only)
 * Ties break on published_at (newest first), then id. Every candidate must be published,
 * indexable and not canonicalized elsewhere, and is never the host itself.
 */
class RelatedContentResolver
{
    public const int W_EXPLICIT = 100;

    public const int W_DERIVED = 80;

    public const int W_SAME_CATEGORY = 30;

    public const int W_SHARED_INDUSTRY = 25;

    public const int W_SHARED_TAG = 20;

    public const int W_SHARED_TAG_CAP = 60;

    public const int W_FEATURED = 5;

    protected const int CANDIDATES = 12;

    public function __construct(private readonly IndexabilityResolver $indexability) {}

    /**
     * @return Collection<int, Service>
     */
    public function services(Model $host, int $limit = 4): Collection
    {
        $scored = $this->explicit($host, $host instanceof Service ? 'relatedServices' : 'services', Service::query()->published()->ordered()->with(['category', 'seo']));

        if ($host instanceof Service) {
            $this->add($scored, Service::query()->published()->ordered()->with(['category', 'seo'])
                ->where('service_category_id', $host->service_category_id)->whereKeyNot($host->getKey())->limit(self::CANDIDATES)->get(), self::W_SAME_CATEGORY);
        }

        return $this->rank($scored, $host, $limit);
    }

    /**
     * Sibling documentation pages of the same product, in reading order.
     *
     * @return Collection<int, ProductDocument>
     */
    public function productDocuments(ProductDocument $document): Collection
    {
        return ProductDocument::query()->where('product_id', $document->product_id)->published()->ordered()->get(['id', 'product_id', 'title', 'slug', 'section', 'sort_order']);
    }

    /**
     * @return Collection<int, Product>
     */
    public function products(Model $host, int $limit = 3): Collection
    {
        $scored = $this->explicit($host, 'products', Product::query()->publiclyVisible()->ordered()->with(['media', 'seo']));

        if ($scored === []) {
            // Documented weaker fallback: featured products are an editorial choice, not random filler.
            $this->add($scored, Product::query()->publiclyVisible()->featured()->ordered()->with(['media', 'seo'])->limit(self::CANDIDATES)->get(), self::W_FEATURED);
        }

        return $this->rank($scored, $host, $limit);
    }

    /**
     * @return Collection<int, Solution>
     */
    public function solutions(Model $host, int $limit = 4): Collection
    {
        return $this->rank($this->explicit($host, 'solutions', Solution::query()->published()->ordered()->with('seo')), $host, $limit);
    }

    /**
     * @return Collection<int, Industry>
     */
    public function industries(Model $host, int $limit = 6): Collection
    {
        return $this->rank($this->explicit($host, 'industries', Industry::query()->published()->ordered()->with('seo')), $host, $limit);
    }

    /**
     * @return Collection<int, Article>
     */
    public function articles(Model $host, int $limit = 3): Collection
    {
        $base = fn () => Article::query()->published()->with(['author', 'category', 'media', 'seo'])->latest('published_at');
        $scored = $this->explicit($host, $host instanceof Article ? 'relatedArticles' : 'articles', $base());

        if ($host instanceof Article) {
            if ($host->article_category_id) {
                $this->add($scored, $base()->where('article_category_id', $host->article_category_id)->whereKeyNot($host->getKey())->limit(self::CANDIDATES)->get(), self::W_SAME_CATEGORY);
            }

            $tagIds = $host->tags()->pluck('tags.id');

            if ($tagIds->isNotEmpty()) {
                $shared = $base()->whereKeyNot($host->getKey())
                    ->withCount(['tags as shared_tags' => fn ($q) => $q->whereIn('tags.id', $tagIds)])
                    ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds))
                    ->limit(self::CANDIDATES)->get();

                foreach ($shared as $article) {
                    $this->addOne($scored, $article, min(self::W_SHARED_TAG * (int) $article->shared_tags, self::W_SHARED_TAG_CAP));
                }
            }
        }

        return $this->rank($scored, $host, $limit);
    }

    /**
     * @return Collection<int, CaseStudy>
     */
    public function caseStudies(Model $host, int $limit = 3): Collection
    {
        $base = fn () => CaseStudy::query()->published()->with(['client', 'industry', 'media', 'seo'])->latest('published_at');
        $scored = $this->explicit($host, 'caseStudies', $base());

        if ($host instanceof Solution) {
            $serviceIds = $host->services()->published()->pluck('services.id');
            $productIds = $host->products()->publiclyVisible()->pluck('products.id');

            $this->add($scored, $base()->where(fn ($q) => $q
                ->whereHas('services', fn ($s) => $s->whereIn('services.id', $serviceIds))
                ->orWhereHas('products', fn ($p) => $p->whereIn('products.id', $productIds)))
                ->limit(self::CANDIDATES)->get(), self::W_DERIVED);
        }

        if ($host instanceof Service || $host instanceof Product) {
            $industryIds = $host->industries()->published()->pluck('industries.id');

            if ($industryIds->isNotEmpty()) {
                $this->add($scored, $base()->whereIn('industry_id', $industryIds)->limit(self::CANDIDATES)->get(), self::W_SHARED_INDUSTRY);
            }
        }

        return $this->rank($scored, $host, $limit);
    }

    /**
     * Mixed discovery list for the related_content block: strongest signals across types.
     *
     * @return Collection<int, Model>
     */
    public function discover(Model $host, int $limit = 6): Collection
    {
        $scored = [];

        foreach ([$this->articles($host, $limit), $this->caseStudies($host, $limit), $this->solutions($host, $limit), $this->services($host, $limit)] as $collection) {
            foreach ($collection as $index => $model) {
                $this->addOne($scored, $model, self::W_EXPLICIT - $index);
            }
        }

        return $this->rank($scored, $host, $limit);
    }

    /**
     * Records attached through an explicit CMS relationship, in the admin's order.
     *
     * @param  Builder<Model>  $eligible
     * @return array<string, array{model: Model, score: int}>
     */
    protected function explicit(Model $host, string $relation, $eligible): array
    {
        $scored = [];

        if (! method_exists($host, $relation)) {
            return $scored;
        }

        $query = $host->{$relation}();

        if (! $query instanceof BelongsToMany && ! $query instanceof HasMany && ! $query instanceof MorphToMany) {
            return $scored;
        }

        $ids = $query->getQuery()->getModel()->exists ? [] : $host->{$relation}()->pluck($query->getRelated()->getQualifiedKeyName())->all();

        if ($ids === []) {
            return $scored;
        }

        $records = $eligible->whereKey($ids)->limit(self::CANDIDATES * 2)->get();

        foreach ($records as $model) {
            $position = array_search($model->getKey(), $ids, true);
            $this->addOne($scored, $model, self::W_EXPLICIT + max(0, self::CANDIDATES - (int) $position));
        }

        return $scored;
    }

    /**
     * @param  array<string, array{model: Model, score: int}>  $scored
     * @param  iterable<Model>  $models
     */
    protected function add(array &$scored, iterable $models, int $weight): void
    {
        foreach ($models as $model) {
            $this->addOne($scored, $model, $weight);
        }
    }

    /**
     * @param  array<string, array{model: Model, score: int}>  $scored
     */
    protected function addOne(array &$scored, Model $model, int $weight): void
    {
        $key = $model->getMorphClass().':'.$model->getKey();

        if (isset($scored[$key])) {
            $scored[$key]['score'] += $weight;

            return;
        }

        $scored[$key] = ['model' => $model, 'score' => $weight];
    }

    /**
     * Filters to discoverable records, excludes the host and sorts deterministically.
     *
     * @param  array<string, array{model: Model, score: int}>  $scored
     * @return Collection<int, Model>
     */
    protected function rank(array $scored, Model $host, int $limit): Collection
    {
        $items = collect($scored)
            ->reject(fn (array $item): bool => $item['model']->is($host) || ! $this->discoverable($item['model']))
            ->sort(function (array $a, array $b): int {
                return [$b['score'], $b['model']->published_at?->getTimestamp() ?? 0, $b['model']->getKey()]
                    <=> [$a['score'], $a['model']->published_at?->getTimestamp() ?? 0, $a['model']->getKey()];
            })
            ->take($limit)
            ->map(fn (array $item): Model => $item['model'])
            ->values();

        return new Collection($items->all());
    }

    protected function discoverable(Model $model): bool
    {
        return $this->indexability->forEntity($model)->discoverable;
    }
}
