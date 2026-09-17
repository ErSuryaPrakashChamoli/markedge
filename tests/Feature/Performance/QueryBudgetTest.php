<?php

use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Form;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use Illuminate\Support\Facades\DB;

/**
 * Query budgets for representative public pages (Phase 10 §15). Budgets are set from measured
 * values with headroom; a failure means a real regression, not a brittle number.
 */
function queriesFor(string $uri): array
{
    test()->get($uri)->assertOk();
    DB::flushQueryLog();
    DB::enableQueryLog();
    test()->get($uri)->assertOk();
    $log = DB::getQueryLog();
    DB::disableQueryLog();

    // The page-view event (Phase 13) is one insert written after the response and is not part of the page budget.
    return array_values(array_filter(array_map(fn (array $q) => $q['query'], $log), fn (string $sql) => ! str_starts_with($sql, 'insert into "conversion_events"')));
}

function seedCatalogue(): array
{
    $category = ServiceCategory::factory()->published()->create(['slug' => 'technology']);
    $services = Service::factory()->for($category, 'category')->published()->count(6)->create();
    $products = Product::factory()->active()->count(2)->create();
    $solutions = Solution::factory()->published()->count(3)->create();
    $industries = Industry::factory()->published()->count(4)->create();
    $articles = Article::factory()->published()->count(5)->create();
    $cases = CaseStudy::factory()->published()->count(3)->create(['industry_id' => $industries->first()->id]);
    $host = $services->first();
    $host->solutions()->attach($solutions);
    $host->industries()->attach($industries);
    $host->products()->attach($products);
    foreach ($articles->take(3) as $article) {
        $article->services()->attach($host);
    }
    $host->faqs()->createMany([['question' => 'Q1', 'answer' => 'A1', 'is_visible' => true], ['question' => 'Q2', 'answer' => 'A2', 'is_visible' => true]]);
    $products->first()->solutions()->attach($solutions);
    $products->first()->industries()->attach($industries);

    return compact('category', 'services', 'products', 'solutions', 'industries', 'articles', 'cases', 'host');
}

it('keeps representative public pages within their query budgets', function () {
    $data = seedCatalogue();
    Page::factory()->published()->create(['slug' => 'home']);
    Page::factory()->published()->create(['slug' => 'contact', 'form_id' => Form::factory()->create()->id]);
    LandingPage::factory()->published()->create(['slug' => 'automation', 'form_id' => Form::factory()->create()->id]);

    $budgets = [
        // Measured (SQLite, array cache): 7, 6, 10, 36, 21, 17, 16, 10, 22, 10, 9, 10, 6, 4. Budget = measured + ~30%.
        '/' => 10,
        '/services' => 9,
        '/services/technology' => 14,
        '/services/'.$data['host']->slug => 40,
        '/products/'.$data['products']->first()->slug => 28,
        '/solutions/'.$data['solutions']->first()->slug => 23,
        '/industries/'.$data['industries']->first()->slug => 21,
        '/case-studies/'.$data['cases']->first()->slug => 14,
        '/insights/'.$data['articles']->first()->slug => 29,
        '/insights' => 14,
        '/contact' => 12,
        '/lp/automation' => 14,
        '/search?q=service' => 9,
        '/search' => 6,
    ];

    $report = [];

    foreach ($budgets as $uri => $budget) {
        $queries = queriesFor($uri);
        $report[$uri] = count($queries);

        expect(count($queries))->toBeLessThanOrEqual($budget, "{$uri} ran ".count($queries)." queries (budget {$budget}):\n".implode("\n", $queries));
    }

    fwrite(STDERR, "\nQuery counts: ".json_encode($report)."\n");
});

it('does not repeat identical queries on a service detail page (no N+1)', function () {
    $data = seedCatalogue();

    $normalised = array_map(fn (string $sql) => preg_replace('/\d+/', 'N', $sql), queriesFor('/services/'.$data['host']->slug));
    // Each related-content collection eager-loads its own seo rows, so the same shape appears once per relation.
    $duplicates = array_filter(array_count_values($normalised), fn (int $n) => $n > 4);

    expect($duplicates)->toBe([], 'Repeated query shapes: '.json_encode($duplicates));
});

it('does not repeat identical queries on the home page', function () {
    seedCatalogue();
    Page::factory()->published()->create(['slug' => 'home']);

    $normalised = array_map(fn (string $sql) => preg_replace('/\d+/', 'N', $sql), queriesFor('/'));
    $duplicates = array_filter(array_count_values($normalised), fn (int $n) => $n > 3);

    expect($duplicates)->toBe([], 'Repeated query shapes: '.json_encode($duplicates));
});
