<?php

namespace App\Services\Cms;

use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\Product;
use App\Models\Service;
use App\Models\Solution;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Related content rules from architecture §17.2. Every result is published-only and bounded.
 */
class RelatedContentResolver
{
    /**
     * @return Collection<int, Service>
     */
    public function services(Model $host, int $limit = 4): Collection
    {
        $related = $this->fromRelation($host, $host instanceof Service ? 'relatedServices' : 'services', fn ($q) => $q->published()->ordered()->with('category'), $limit);

        if ($related->isNotEmpty() || ! $host instanceof Service) {
            return $related;
        }

        return Service::query()->published()->ordered()->with('category')
            ->where('service_category_id', $host->service_category_id)
            ->whereKeyNot($host->getKey())
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function products(Model $host, int $limit = 3): Collection
    {
        $related = $this->fromRelation($host, 'products', fn ($q) => $q->publiclyVisible()->ordered()->with('media'), $limit);

        if ($related->isNotEmpty()) {
            return $related;
        }

        return Product::query()->publiclyVisible()->featured()->ordered()->with('media')
            ->when($host instanceof Product, fn ($q) => $q->whereKeyNot($host->getKey()))
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Solution>
     */
    public function solutions(Model $host, int $limit = 4): Collection
    {
        return $this->fromRelation($host, 'solutions', fn ($q) => $q->published()->ordered(), $limit);
    }

    /**
     * @return Collection<int, Industry>
     */
    public function industries(Model $host, int $limit = 6): Collection
    {
        return $this->fromRelation($host, 'industries', fn ($q) => $q->published()->ordered(), $limit);
    }

    /**
     * @return Collection<int, Article>
     */
    public function articles(Model $host, int $limit = 3): Collection
    {
        $relation = $host instanceof Article ? 'relatedArticles' : 'articles';
        $related = $this->fromRelation($host, $relation, fn ($q) => $q->published()->with(['author', 'category', 'media'])->latest('published_at'), $limit);

        if ($related->isNotEmpty() || ! $host instanceof Article) {
            return $related;
        }

        return Article::query()->published()->with(['author', 'category', 'media'])
            ->where('article_category_id', $host->article_category_id)
            ->whereKeyNot($host->getKey())
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, CaseStudy>
     */
    public function caseStudies(Model $host, int $limit = 3): Collection
    {
        if ($host instanceof Solution) {
            $serviceIds = $host->services()->published()->pluck('services.id');
            $productIds = $host->products()->publiclyVisible()->pluck('products.id');

            return CaseStudy::query()->published()->with(['client', 'industry', 'media'])
                ->where(fn ($q) => $q
                    ->whereHas('services', fn ($s) => $s->whereIn('services.id', $serviceIds))
                    ->orWhereHas('products', fn ($p) => $p->whereIn('products.id', $productIds)))
                ->latest('published_at')
                ->limit($limit)
                ->get();
        }

        return $this->fromRelation($host, 'caseStudies', fn ($q) => $q->published()->with(['client', 'industry', 'media'])->latest('published_at'), $limit);
    }

    /**
     * @return Collection<int, Model>
     */
    protected function fromRelation(Model $host, string $relation, \Closure $scope, int $limit): Collection
    {
        if (! method_exists($host, $relation)) {
            return new Collection;
        }

        $query = $host->{$relation}();

        if (! $query instanceof BelongsToMany && ! $query instanceof HasMany) {
            return new Collection;
        }

        return $scope($query)->limit($limit)->get();
    }
}
