<?php

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use App\Services\Cms\PublicUrl;

it('maps each entity to its canonical path', function (string $model, string $expected) {
    $record = $model::factory()->create(['slug' => 'example']);

    expect(app(PublicUrl::class)->pathFor($record))->toBe($expected);
})->with([
    'page' => [Page::class, '/example'],
    'service category' => [ServiceCategory::class, '/services/example'],
    'service' => [Service::class, '/services/example'],
    'product' => [Product::class, '/products/example'],
    'solution' => [Solution::class, '/solutions/example'],
    'industry' => [Industry::class, '/industries/example'],
    'case study' => [CaseStudy::class, '/case-studies/example'],
    'article' => [Article::class, '/insights/example'],
    'article category' => [ArticleCategory::class, '/insights/category/example'],
    'landing page' => [LandingPage::class, '/lp/example'],
]);

it('maps the home page to the site root', function () {
    expect(app(PublicUrl::class)->pathFor(Page::factory()->home()->create()))->toBe('/');
});

it('treats coming-soon products as visible but drafts as hidden', function () {
    $urls = app(PublicUrl::class);

    expect($urls->isPubliclyVisible(Product::factory()->comingSoon()->create()))->toBeTrue()
        ->and($urls->isPubliclyVisible(Product::factory()->create()))->toBeFalse()
        ->and($urls->isPubliclyVisible(Service::factory()->published()->create()))->toBeTrue()
        ->and($urls->isPubliclyVisible(Service::factory()->scheduled()->create()))->toBeFalse();
});
