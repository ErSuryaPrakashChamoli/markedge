<?php

use App\Models\Article;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Search\Contracts\SearchEngine;
use App\Search\SearchDocument;
use App\Search\SearchQuery;
use App\Search\SearchResults;

it('shows a useful search interface without a query', function () {
    $this->get('/search')->assertOk()
        ->assertSee('Search Markedge')
        ->assertSee('Find services, products, solutions')
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<link rel="canonical" href="http://localhost/search">', false)
        ->assertSee('role="search"', false)
        ->assertSee('for="search-q"', false);
});

it('returns published results with type labels and canonical URLs', function () {
    $category = ServiceCategory::factory()->published()->create(['name' => 'Technology', 'slug' => 'technology']);
    Service::factory()->for($category, 'category')->published()->create(['name' => 'Software Development', 'slug' => 'software-development', 'short_description' => 'Custom software built for your business.']);
    Product::factory()->active()->create(['name' => 'Lead Management Software', 'slug' => 'lms']);
    Service::factory()->for($category, 'category')->create(['name' => 'Software Draft']);

    $this->get('/search?q=software')->assertOk()
        ->assertSee('Software Development')
        ->assertSee('Lead Management Software')
        ->assertDontSee('Software Draft')
        ->assertSee('http://localhost/services/software-development')
        ->assertSee('Service</span>', false)
        ->assertSee('Product</span>', false)
        ->assertSee('Custom software built for your business.')
        ->assertSee('results for “software”')
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<link rel="canonical" href="http://localhost/search">', false);
});

it('normalises whitespace and case without changing the term shown', function () {
    Service::factory()->published()->create(['name' => 'Cloud Infrastructure']);

    $this->get('/search?q=%20%20CLOUD%20%20%20infrastructure%20')->assertOk()
        ->assertSee('Cloud Infrastructure')
        ->assertSee('for “CLOUD infrastructure”');
});

it('rejects queries below the minimum length without erroring', function () {
    $this->get('/search?q=a')->assertOk()->assertSee('Enter at least 2 characters')->assertDontSee('results for');
});

it('bounds oversized queries and still answers', function () {
    Service::factory()->published()->create(['name' => 'Networking']);
    $long = str_repeat('networking ', 60);

    $this->get('/search?q='.urlencode($long))->assertOk()->assertSee('Networking');
    $this->get('/search?q='.urlencode(str_repeat('x', 1500)))->assertStatus(302);
});

it('shows a clear no-results state with the query echoed safely', function () {
    $this->get('/search?q=xyz-no-result')->assertOk()
        ->assertSee('No results for “xyz-no-result”')
        ->assertSee('Explore services');

    $response = $this->get('/search?q='.urlencode('<script>alert(1)</script> plan'))->assertOk();
    expect($response->getContent())->toContain('&lt;script&gt;')->not->toContain('<script>alert(1)</script>');
});

it('filters by type and category without leaking unpublished content', function () {
    $category = ServiceCategory::factory()->published()->create(['name' => 'Digital Growth', 'slug' => 'digital-growth']);
    Service::factory()->for($category, 'category')->published()->create(['name' => 'Cloud SEO Service']);
    Product::factory()->active()->create(['name' => 'Cloud Product']);
    Article::factory()->create(['title' => 'Cloud draft article']);

    $this->get('/search?q=cloud&type=service')->assertOk()->assertSee('Cloud SEO Service')->assertDontSee('Cloud Product');
    $this->get('/search?q=cloud&type=product')->assertOk()->assertSee('Cloud Product')->assertDontSee('Cloud SEO Service');
    $this->get('/search?q=cloud&category=digital-growth')->assertOk()->assertSee('Cloud SEO Service')->assertDontSee('Cloud Product');
    $this->get('/search?q=cloud&type=article')->assertOk()->assertSee('No results')->assertDontSee('Cloud draft article');
    $this->get('/search?q=cloud&type=lead')->assertStatus(302);
    $this->get('/search?q=cloud&category=Not%20A%20Slug')->assertStatus(302);
});

it('paginates results deterministically', function () {
    Service::factory()->published()->count(13)->sequence(fn ($sequence) => ['name' => 'Automation service '.($sequence->index + 1), 'sort_order' => $sequence->index])->create();

    $first = $this->get('/search?q=automation')->assertOk()->assertSee('13 results')->assertSee('page=2');
    $second = $this->get('/search?q=automation&page=2')->assertOk()->assertSee('rel="prev"', false);

    $this->get('/search?q=automation&page=99')->assertOk()->assertSee('Nothing on this page')->assertSee('Back to the first page');

    expect(substr_count($first->getContent(), 'Automation service'))->toBeGreaterThanOrEqual(10)
        ->and(substr_count($second->getContent(), 'Automation service'))->toBeGreaterThanOrEqual(3);
});

it('handles parameter pollution and injection attempts safely', function () {
    Service::factory()->published()->create(['name' => 'Security Audit']);

    $this->get('/search?q[]=security&q[]=drop')->assertStatus(302);
    $this->get('/search?q='.urlencode("security' OR 1=1 --"))->assertOk()->assertSee('Security Audit');
    $this->get('/search?q='.urlencode('+security* -(audit) @distance "x'))->assertOk()->assertSee('Security Audit');
    $this->get('/search?q=security&page=-1')->assertStatus(302);
});

it('shows a graceful error state when the engine fails', function () {
    $this->app->bind(SearchEngine::class, fn () => new class implements SearchEngine
    {
        public function search(SearchQuery $query): SearchResults
        {
            throw new RuntimeException('SQLSTATE[HY000] boom at /var/app/secret.php');
        }

        public function index(SearchDocument $document): void {}

        public function remove(string $type, int $id): void {}

        public function removeAll(): void {}

        public function stats(): array
        {
            return ['total' => 0, 'by_type' => []];
        }
    });

    $response = $this->get('/search?q=anything')->assertOk()->assertSee('temporarily unavailable');

    expect($response->getContent())->not->toContain('SQLSTATE')->not->toContain('secret.php');
});

it('offers search from the header on every page', function () {
    $this->get('/')->assertSee('aria-label="Search the site"', false)->assertSee('href="http://localhost/search"', false);
});

it('never puts search URLs in the sitemap', function () {
    Service::factory()->published()->create();

    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->not->toContain('/search');
});

it('emits a valid SearchAction on the website schema', function () {
    $service = Service::factory()->published()->create();

    $content = $this->get('/services/'.$service->slug)->getContent();
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $content, $m);
    $graph = collect(json_decode(html_entity_decode($m[1]), true, 512, JSON_THROW_ON_ERROR)['@graph'])->keyBy('@type');

    expect($graph['WebSite']['potentialAction']['@type'])->toBe('SearchAction')
        ->and($graph['WebSite']['potentialAction']['target']['urlTemplate'])->toBe('http://localhost/search?q={search_term_string}')
        ->and($graph['WebSite']['potentialAction']['query-input'])->toBe('required name=search_term_string');

    $this->get('/search?q=anything')->assertOk();
});

it('ranks exact title matches above summary and body matches', function () {
    Service::factory()->published()->create(['name' => 'Backup and Recovery', 'short_description' => 'Continuity planning.', 'overview' => '<p>Nothing about the term.</p>']);
    Service::factory()->published()->create(['name' => 'Cybersecurity', 'short_description' => 'Includes backup reviews.', 'overview' => '<p>Firewalls.</p>']);
    Service::factory()->published()->create(['name' => 'Networking', 'short_description' => 'Switching.', 'overview' => '<p>We also handle backup links.</p>']);
    Service::factory()->published()->create(['name' => 'Backup', 'short_description' => 'Exact title.']);

    $this->get('/search?q=backup')->assertOk()->assertSeeInOrder(['>Backup<', 'Backup and Recovery', 'Cybersecurity', 'Networking'], false);
});

it('falls back to any-term matching when no page matches every word', function () {
    Service::factory()->published()->create(['name' => 'Cybersecurity']);

    $this->get('/search?q=cybersecurity+zebra')->assertOk()->assertSee('Cybersecurity')->assertSee('match any of your words');
});
