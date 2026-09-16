<?php

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use App\Models\Tag;
use App\Services\Cms\RelatedContentResolver;
use Illuminate\Support\Facades\DB;

it('ranks explicit links, then tag overlap, then same category for articles', function () {
    $category = ArticleCategory::factory()->create();
    $tagA = Tag::factory()->create();
    $tagB = Tag::factory()->create();
    $host = Article::factory()->published()->create(['article_category_id' => $category->id]);
    $host->tags()->attach([$tagA->id, $tagB->id]);

    $explicit = Article::factory()->published()->create(['title' => 'Explicit']);
    $host->relatedArticles()->attach($explicit, ['sort_order' => 0]);
    $twoTags = Article::factory()->published()->create(['title' => 'Two tags']);
    $twoTags->tags()->attach([$tagA->id, $tagB->id]);
    $oneTag = Article::factory()->published()->create(['title' => 'One tag']);
    $oneTag->tags()->attach($tagA);
    $sameCategory = Article::factory()->published()->create(['title' => 'Same category', 'article_category_id' => $category->id]);
    Article::factory()->published()->create(['title' => 'Unrelated']);

    $titles = app(RelatedContentResolver::class)->articles($host, 6)->pluck('title')->all();

    expect($titles)->toBe(['Explicit', 'Two tags', 'Same category', 'One tag']);
});

it('excludes the host, drafts, noindex and canonicalized records', function () {
    $category = ArticleCategory::factory()->create();
    $host = Article::factory()->published()->create(['article_category_id' => $category->id]);
    Article::factory()->create(['title' => 'Draft sibling', 'article_category_id' => $category->id]);
    $noindex = Article::factory()->published()->create(['title' => 'Noindex sibling', 'article_category_id' => $category->id]);
    SeoMeta::factory()->for($noindex, 'seoable')->noindex()->create();
    $canonicalized = Article::factory()->published()->create(['title' => 'Canonicalized sibling', 'article_category_id' => $category->id]);
    SeoMeta::factory()->for($canonicalized, 'seoable')->create(['canonical_url' => 'https://example.com/elsewhere']);
    Article::factory()->published()->create(['title' => 'Good sibling', 'article_category_id' => $category->id]);

    $titles = app(RelatedContentResolver::class)->articles($host, 6)->pluck('title')->all();

    expect($titles)->toBe(['Good sibling']);
});

it('keeps admin ordering for explicit relationships and breaks ties by recency', function () {
    $service = Service::factory()->published()->create();
    $this->travelTo('2026-01-01');
    $older = Solution::factory()->published()->create(['name' => 'Older']);
    $this->travelTo('2026-02-01');
    $newer = Solution::factory()->published()->create(['name' => 'Newer']);
    $this->travelBack();
    $service->solutions()->attach([$newer->id => ['sort_order' => 2], $older->id => ['sort_order' => 1]]);

    expect(app(RelatedContentResolver::class)->solutions($service)->pluck('name')->all())->toBe(['Older', 'Newer']);
});

it('adds same-category siblings for services and shared-industry case studies', function () {
    $category = ServiceCategory::factory()->published()->create();
    $host = Service::factory()->for($category, 'category')->published()->create();
    $sibling = Service::factory()->for($category, 'category')->published()->create(['name' => 'Sibling']);
    Service::factory()->published()->create(['name' => 'Other category']);
    $industry = Industry::factory()->published()->create();
    $host->industries()->attach($industry);
    CaseStudy::factory()->published()->create(['title' => 'Industry case', 'industry_id' => $industry->id]);
    CaseStudy::factory()->published()->create(['title' => 'Elsewhere case']);

    $resolver = app(RelatedContentResolver::class);

    expect($resolver->services($host)->pluck('name')->all())->toBe(['Sibling'])
        ->and($resolver->caseStudies($host)->pluck('title')->all())->toBe(['Industry case']);
});

it('returns nothing rather than random filler when no signals exist', function () {
    $host = Industry::factory()->published()->create();
    Article::factory()->published()->count(3)->create();
    Solution::factory()->published()->count(2)->create();

    $resolver = app(RelatedContentResolver::class);

    expect($resolver->articles($host))->toBeEmpty()
        ->and($resolver->solutions($host))->toBeEmpty()
        ->and($resolver->discover($host))->toBeEmpty();
});

it('uses featured products as the documented weaker product fallback only', function () {
    $host = Service::factory()->published()->create();
    Product::factory()->active()->featured()->create(['name' => 'Featured product']);
    Product::factory()->active()->create(['name' => 'Plain product']);

    expect(app(RelatedContentResolver::class)->products($host)->pluck('name')->all())->toBe(['Featured product']);
});

it('renders related content on entity pages within the query budget', function () {
    $category = ServiceCategory::factory()->published()->create();
    $host = Service::factory()->for($category, 'category')->published()->create();
    Service::factory()->for($category, 'category')->published()->count(5)->create();
    $articles = Article::factory()->published()->count(4)->create();
    foreach ($articles as $article) {
        $article->services()->attach($host);
    }
    $host->solutions()->attach(Solution::factory()->published()->count(3)->create());
    $host->industries()->attach(Industry::factory()->published()->count(3)->create());

    DB::enableQueryLog();
    $this->get('/services/'.$host->slug)->assertOk();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($count)->toBeLessThan(55);
});

it('links related items to their canonical URLs', function () {
    $host = Article::factory()->published()->create();
    $service = Service::factory()->published()->create(['slug' => 'cloud-solutions']);
    $host->services()->attach($service);

    $this->get('/insights/'.$host->slug)->assertSee('href="/services/cloud-solutions"', false);
});
