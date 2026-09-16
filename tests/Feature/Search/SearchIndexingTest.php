<?php

use App\Enums\PublishStatus;
use App\Filament\Pages\SearchIndexStatus;
use App\Jobs\SyncSearchEntry;
use App\Models\Article;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Product;
use App\Models\SearchEntry;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Tag;
use App\Search\Contracts\SearchEngine;
use App\Search\SearchIndexer;
use App\Services\Cms\PreviewLink;
use App\Services\Cms\Publisher;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function indexed(string $type, int $id): ?SearchEntry
{
    return SearchEntry::query()->where('searchable_type', $type)->where('searchable_id', $id)->first();
}

it('indexes a record when it is published and removes it when unpublished or archived', function () {
    $service = Service::factory()->create(['name' => 'Cloud Infrastructure', 'slug' => 'cloud']);
    expect(indexed('service', $service->id))->toBeNull();

    app(Publisher::class)->publish($service);
    $entry = indexed('service', $service->id);
    expect($entry)->not->toBeNull()->and($entry->title)->toBe('Cloud Infrastructure')->and($entry->url)->toBe('/services/cloud')->and($entry->kind)->toBe('service');

    app(Publisher::class)->unpublish($service);
    expect(indexed('service', $service->id))->toBeNull();

    app(Publisher::class)->publish($service);
    app(Publisher::class)->archive($service);
    expect(indexed('service', $service->id))->toBeNull();
});

it('keeps review and scheduled content out of the index', function () {
    $review = Service::factory()->create(['status' => PublishStatus::Review]);
    $scheduled = Service::factory()->scheduled()->create();

    expect(SearchEntry::count())->toBe(0);
    expect(indexed('service', $review->id))->toBeNull()->and(indexed('service', $scheduled->id))->toBeNull();
});

it('removes content that becomes noindex or canonicalized elsewhere', function () {
    $service = Service::factory()->published()->create();
    expect(indexed('service', $service->id))->not->toBeNull();

    $seo = SeoMeta::factory()->for($service, 'seoable')->create(['robots_index' => false]);
    expect(indexed('service', $service->id))->toBeNull();

    $seo->update(['robots_index' => true]);
    expect(indexed('service', $service->id))->not->toBeNull();

    $seo->update(['canonical_url' => 'https://example.com/original']);
    expect(indexed('service', $service->id))->toBeNull();
});

it('updates the indexed URL on slug change and removes deleted content', function () {
    $service = Service::factory()->published()->create(['slug' => 'old-slug']);
    $service->update(['slug' => 'new-slug']);
    expect(indexed('service', $service->id)->url)->toBe('/services/new-slug');

    $service->delete();
    expect(indexed('service', $service->id))->toBeNull();

    $service->restore();
    expect(indexed('service', $service->id))->not->toBeNull();

    $service->forceDelete();
    expect(SearchEntry::where('searchable_id', $service->id)->exists())->toBeFalse();
});

it('indexes taxonomy and keywords for articles and products', function () {
    $article = Article::factory()->published()->create(['title' => 'Automating hiring']);
    $article->tags()->attach(Tag::factory()->create(['name' => 'Recruitment']));
    $article->touch();
    $product = Product::factory()->active()->create(['name' => 'RMS']);
    $product->features()->create(['title' => 'Interview scheduling']);
    $product->touch();

    expect(indexed('article', $article->id)->keywords)->toBe('Recruitment')
        ->and(indexed('article', $article->id)->category_slug)->toBe($article->category->slug)
        ->and(indexed('product', $product->id)->body_text)->toContain('Interview scheduling');

    $this->get('/search?q=recruitment')->assertSee('Automating hiring');
    $this->get('/search?q=interview')->assertSee('RMS');
});

it('keeps on-site search working when the environment is closed to search engines', function () {
    config()->set('markedge.seo.indexable', false);
    $service = Service::factory()->published()->create(['name' => 'Staging Service']);
    $noindex = Service::factory()->published()->create(['name' => 'Hidden Service']);
    SeoMeta::factory()->for($noindex, 'seoable')->create(['robots_index' => false]);

    expect(indexed('service', $service->id))->not->toBeNull()->and(indexed('service', $noindex->id))->toBeNull();
    $this->get('/search?q=service')->assertOk()->assertSee('Staging Service')->assertDontSee('Hidden Service')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('never indexes internal records or previews', function () {
    Lead::factory()->create(['name' => 'Private Lead']);
    $draft = Page::factory()->create(['title' => 'Secret draft']);
    $this->get(app(PreviewLink::class)->for($draft, 1))->assertOk();

    expect(SearchEntry::count())->toBe(0);
    $this->get('/search?q=private')->assertSee('No results');
    $this->get('/search?q=secret')->assertSee('No results');
});

it('rebuilds the index in chunks without touching content', function () {
    Service::factory()->published()->count(7)->create();
    Service::factory()->count(2)->create();
    Product::factory()->active()->create();
    SearchEntry::query()->create(['searchable_type' => 'service', 'searchable_id' => 999999, 'kind' => 'service', 'title' => 'Stale', 'url' => '/services/stale']);
    $before = Service::query()->withTrashed()->get()->map(fn (Service $s) => [$s->id, $s->status->value, $s->slug])->all();

    DB::enableQueryLog();
    $this->artisan('markedge:search-reindex', ['--chunk' => 3])->expectsOutputToContain('8 documents indexed')->assertSuccessful()->run();
    $queries = collect(DB::getQueryLog());
    DB::disableQueryLog();

    expect(SearchEntry::count())->toBe(8)
        ->and(SearchEntry::where('title', 'Stale')->exists())->toBeFalse()
        ->and(Service::query()->withTrashed()->get()->map(fn (Service $s) => [$s->id, $s->status->value, $s->slug])->all())->toBe($before)
        ->and($queries->filter(fn (array $q) => str_contains($q['query'], 'from "services"') && str_contains($q['query'], 'limit 3'))->count())->toBeGreaterThanOrEqual(3)
        ->and(Setting::valueOf('search.last_rebuilt_at'))->not->toBeNull();
});

it('reports missing and stale documents in the audit', function () {
    $service = Service::factory()->published()->create();
    SearchEntry::query()->where('searchable_id', $service->id)->delete();
    SearchEntry::query()->create(['searchable_type' => 'article', 'searchable_id' => 424242, 'kind' => 'article', 'title' => 'Ghost', 'url' => '/insights/ghost']);

    $audit = app(SearchIndexer::class)->audit();

    expect($audit['by_type']['service']['missing'])->toBe(1)
        ->and($audit['by_type']['article']['stale'])->toBe(1);
});

it('does not resurrect content unpublished after the sync job was dispatched', function () {
    $service = Service::factory()->published()->create();
    $service->forceFill(['status' => PublishStatus::Draft])->saveQuietly();

    (new SyncSearchEntry('service', $service->id))->handle(app(SearchIndexer::class), app(SearchEngine::class));

    expect(indexed('service', $service->id))->toBeNull();
});

it('lets SEO managers open the index status page and rebuild', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
    Service::factory()->published()->create();
    SearchEntry::query()->delete();

    $this->actingAs(adminUser('SEO Manager'));
    Livewire::test(SearchIndexStatus::class)->assertSee('Missing or stale')->callAction('rebuild')->assertNotified();
    expect(SearchEntry::count())->toBe(1);

    $this->actingAs(adminUser('Sales'));
    $this->get(SearchIndexStatus::getUrl())->assertForbidden();
});
