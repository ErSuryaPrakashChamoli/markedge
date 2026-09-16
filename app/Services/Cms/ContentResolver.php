<?php

namespace App\Services\Cms;

use App\Enums\ProductStatus;
use App\Enums\PublishStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Author;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Every public query in one place: published-only, slug-based, eager loaded.
 * A slug that exists but is archived returns 410 (architecture §34).
 */
class ContentResolver
{
    public function homePage(): ?Page
    {
        return Page::query()->published()->where('slug', Page::HOME_SLUG)->with(['seo', 'cta', 'form.fields', 'faqs' => fn ($q) => $q->visible()])->first();
    }

    public function page(string $slug): ?Page
    {
        return $this->findPublished(Page::query()->with(['seo', 'cta', 'form.fields', 'faqs' => fn ($q) => $q->visible()]), $slug);
    }

    /**
     * @return Collection<int, ServiceCategory>
     */
    public function serviceCategories(): Collection
    {
        return ServiceCategory::query()
            ->published()->ordered()
            ->with(['services' => fn ($q) => $q->published()->ordered(), 'media'])
            ->get();
    }

    public function serviceCategory(string $slug): ?ServiceCategory
    {
        return $this->findPublished(ServiceCategory::query()->with([
            'seo', 'cta', 'media',
            'services' => fn ($q) => $q->published()->ordered()->with('media'),
            'faqs' => fn ($q) => $q->visible(),
        ]), $slug);
    }

    public function service(string $slug): ?Service
    {
        return $this->findPublished(Service::query()->with([
            'category', 'seo', 'cta', 'media', 'technologies',
            'faqs' => fn ($q) => $q->visible(),
        ]), $slug);
    }

    /**
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        return Product::query()->publiclyVisible()->ordered()->with('media')->get();
    }

    public function product(string $slug): ?Product
    {
        $product = Product::query()->where('slug', $slug)->with([
            'seo', 'cta', 'media', 'demoForm.fields', 'technologies',
            'features', 'modules.media',
            'industries' => fn ($q) => $q->published()->ordered(),
            'services' => fn ($q) => $q->published()->ordered()->with('category'),
            'solutions' => fn ($q) => $q->published()->ordered(),
            'caseStudies' => fn ($q) => $q->published()->with(['client', 'industry', 'media']),
            'testimonials' => fn ($q) => $q->visible()->ordered()->with(['client', 'media']),
            'faqs' => fn ($q) => $q->visible(),
        ])->first();

        if ($product === null) {
            return null;
        }

        if (! $product->isPubliclyVisible()) {
            abort($product->status === ProductStatus::Archived ? 410 : 404);
        }

        return $product;
    }

    /**
     * @return Collection<int, Solution>
     */
    public function solutions(): Collection
    {
        return Solution::query()->published()->orderByDesc('is_featured')->ordered()->with('media')->get();
    }

    public function solution(string $slug): ?Solution
    {
        return $this->findPublished(Solution::query()->with([
            'seo', 'cta', 'media', 'technologies',
            'services' => fn ($q) => $q->published()->ordered()->with('category'),
            'products' => fn ($q) => $q->publiclyVisible()->ordered()->with('media'),
            'industries' => fn ($q) => $q->published()->ordered(),
            'faqs' => fn ($q) => $q->visible(),
        ]), $slug);
    }

    /**
     * @return Collection<int, Industry>
     */
    public function industries(): Collection
    {
        return Industry::query()->published()->orderByDesc('is_featured')->ordered()->with('media')->get();
    }

    public function industry(string $slug): ?Industry
    {
        return $this->findPublished(Industry::query()->with([
            'seo', 'cta', 'media', 'technologies',
            'solutions' => fn ($q) => $q->published()->ordered(),
            'services' => fn ($q) => $q->published()->ordered()->with('category'),
            'products' => fn ($q) => $q->publiclyVisible()->ordered()->with('media'),
            'caseStudies' => fn ($q) => $q->published()->with(['client', 'media']),
            'faqs' => fn ($q) => $q->visible(),
        ]), $slug);
    }

    /**
     * @return Collection<int, CaseStudy>
     */
    public function caseStudies(): Collection
    {
        return CaseStudy::query()->published()->orderByDesc('is_featured')->latest('published_at')->with(['client', 'industry', 'media'])->get();
    }

    public function caseStudy(string $slug): ?CaseStudy
    {
        return $this->findPublished(CaseStudy::query()->with([
            'client', 'industry', 'seo', 'cta', 'media', 'technologies',
            'services' => fn ($q) => $q->published()->ordered()->with('category'),
            'products' => fn ($q) => $q->publiclyVisible()->ordered()->with('media'),
        ]), $slug);
    }

    /**
     * @return LengthAwarePaginator<int, Article>
     */
    public function articles(?ArticleCategory $category = null, ?Tag $tag = null, ?Author $author = null, int $perPage = 12): LengthAwarePaginator
    {
        return Article::query()->published()
            ->with(['author', 'category', 'media'])
            ->when($category, fn (Builder $q) => $q->where('article_category_id', $category->id))
            ->when($tag, fn (Builder $q) => $q->whereHas('tags', fn ($t) => $t->whereKey($tag->id)))
            ->when($author, fn (Builder $q) => $q->where('author_id', $author->id))
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Article>
     */
    public function featuredArticles(int $limit = 1): Collection
    {
        return Article::query()->published()->featured()->with(['author', 'category', 'media'])->latest('published_at')->limit($limit)->get();
    }

    /**
     * @return Collection<int, ArticleCategory>
     */
    public function articleCategories(): Collection
    {
        return ArticleCategory::query()
            ->visible()->ordered()
            ->whereHas('articles', fn ($q) => $q->published())
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->get();
    }

    public function articleCategory(string $slug): ?ArticleCategory
    {
        return ArticleCategory::query()->visible()->where('slug', $slug)->with('seo')->first();
    }

    public function tag(string $slug): ?Tag
    {
        return Tag::query()->where('slug', $slug)->first();
    }

    public function author(string $slug): ?Author
    {
        return Author::query()->visible()->where('slug', $slug)->with(['seo', 'media'])->first();
    }

    public function article(string $slug): ?Article
    {
        return $this->findPublished(Article::query()->with([
            'author.media', 'category', 'tags', 'seo', 'cta', 'media',
            'faqs' => fn ($q) => $q->visible(),
        ]), $slug);
    }

    public function landingPage(string $slug): ?LandingPage
    {
        return $this->findPublished(LandingPage::query()->with([
            'campaign', 'form.fields', 'cta', 'seo', 'media',
            'faqs' => fn ($q) => $q->visible(),
        ]), $slug);
    }

    /**
     * Published record for the slug, 410 when it is archived, null when it does not exist or is not public.
     */
    protected function findPublished(Builder $query, string $slug): ?Model
    {
        $record = $query->where('slug', $slug)->first();

        if ($record === null) {
            return null;
        }

        if ($record->isPublished()) {
            return $record;
        }

        if ($record->status === PublishStatus::Archived) {
            abort(410);
        }

        return null;
    }
}
