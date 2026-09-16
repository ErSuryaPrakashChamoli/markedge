<?php

use App\Models\Article;
use App\Models\Industry;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\DB;

/**
 * Representative pages must stay within a query budget so N+1 regressions are caught early.
 */
it('renders a fully related service page within the query budget', function () {
    $category = ServiceCategory::factory()->published()->create();
    $service = Service::factory()->for($category, 'category')->published()->create();
    Service::factory()->for($category, 'category')->published()->count(4)->create();
    $service->products()->attach(Product::factory()->active()->count(3)->create());
    $service->industries()->attach(Industry::factory()->published()->count(4)->create());
    $articles = Article::factory()->published()->count(3)->create();
    foreach ($articles as $article) {
        $article->services()->attach($service);
    }

    DB::enableQueryLog();
    $this->get('/services/'.$service->slug)->assertOk();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($count)->toBeLessThan(45);
});

it('renders the CMS home page with many blocks within the query budget', function () {
    $category = ServiceCategory::factory()->published()->create();
    Service::factory()->for($category, 'category')->published()->count(6)->create();
    Product::factory()->active()->count(2)->create();
    Article::factory()->published()->count(3)->create();
    Page::factory()->home()->published()->create(['blocks' => [
        ['type' => 'hero', 'data' => ['headline' => 'Home']],
        ['type' => 'capability_intro', 'data' => ['heading' => 'Capabilities']],
        ['type' => 'service_grid', 'data' => ['mode' => 'category', 'service_category_id' => $category->id]],
        ['type' => 'product_showcase', 'data' => ['mode' => 'all_active']],
        ['type' => 'article_grid', 'data' => ['mode' => 'latest']],
    ]]);

    DB::enableQueryLog();
    $this->get('/')->assertOk();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($count)->toBeLessThan(35);
});
