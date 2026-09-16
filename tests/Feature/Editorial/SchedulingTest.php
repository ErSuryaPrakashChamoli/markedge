<?php

use App\Enums\PublishStatus;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Models\Article;
use App\Models\SearchEntry;
use App\Models\Service;
use App\Services\Cms\ContentVersion;
use App\Services\Cms\Publisher;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
});

it('schedules publication with an unpublish date and rejects conflicts', function () {
    $this->actingAs(adminUser());
    $service = Service::factory()->create();

    expect(fn () => app(Publisher::class)->schedule($service, now()->addDays(2), now()->addDay()))->toThrow(ValidationException::class, 'after the publish date');

    app(Publisher::class)->schedule($service, now()->addDay(), now()->addDays(3));
    $service->refresh();

    expect($service->status)->toBe(PublishStatus::Scheduled)
        ->and($service->unpublish_at->isFuture())->toBeTrue()
        ->and($service->isPublished())->toBeFalse();
    $this->get('/services/'.$service->slug)->assertNotFound();
});

it('publishes due content through the scheduler and updates search, sitemap and cache', function () {
    $service = Service::factory()->create(['name' => 'Scheduled Cloud', 'slug' => 'scheduled-cloud', 'status' => PublishStatus::Scheduled, 'published_at' => now()->addMinutes(5)]);
    expect(SearchEntry::query()->where('searchable_id', $service->id)->exists())->toBeFalse();
    $version = app(ContentVersion::class)->current();
    $this->get('/sitemap.xml')->assertDontSee('scheduled-cloud');

    $this->travel(10)->minutes();
    $this->artisan('content:publish-scheduled')->expectsOutputToContain('Published 1')->run();

    expect($service->fresh()->status)->toBe(PublishStatus::Published)
        ->and(SearchEntry::query()->where('searchable_id', $service->id)->exists())->toBeTrue()
        ->and(app(ContentVersion::class)->current())->toBeGreaterThan($version)
        ->and(Activity::query()->where('event', 'published (scheduled)')->exists())->toBeTrue();

    $this->get('/sitemap.xml')->assertSee('scheduled-cloud');
    $this->get('/services/scheduled-cloud')->assertOk();
});

it('unpublishes expired content to draft, not archived, and removes it everywhere', function () {
    $service = Service::factory()->published()->create(['slug' => 'expiring', 'unpublish_at' => now()->addMinutes(5)]);
    $this->get('/sitemap.xml')->assertSee('/services/expiring');

    $this->travel(10)->minutes();
    $this->artisan('content:publish-scheduled')->expectsOutputToContain('Unpublished 1')->run();

    $service->refresh();
    expect($service->status)->toBe(PublishStatus::Draft)
        ->and(SearchEntry::query()->where('searchable_id', $service->id)->exists())->toBeFalse()
        ->and(Activity::query()->where('event', 'unpublished (expired)')->exists())->toBeTrue();

    $this->get('/services/expiring')->assertNotFound();
    $this->get('/sitemap.xml')->assertDontSee('/services/expiring');
    $this->get('/search?q=expiring')->assertSee('No results');
});

it('does not publish scheduled content that fails the checklist and logs why', function () {
    $service = Service::factory()->create(['status' => PublishStatus::Scheduled, 'published_at' => now()->subMinute(), 'unpublish_at' => now()->subHour()]);

    $this->artisan('content:publish-scheduled')->run();

    expect($service->fresh()->status)->toBe(PublishStatus::Scheduled)
        ->and(Activity::query()->where('event', 'scheduled publish failed')->exists())->toBeTrue();
});

it('validates schedule fields in the resource form', function () {
    $this->actingAs(adminUser('Super Admin'));
    $article = Article::factory()->create();

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['status' => 'scheduled', 'published_at' => null])
        ->call('save')->assertHasFormErrors(['published_at']);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['published_at' => now()->addDays(2)->startOfMinute(), 'unpublish_at' => now()->addDay()->startOfMinute()])
        ->call('save')->assertHasFormErrors(['unpublish_at']);
});

it('sends one expiry reminder per record to owner and reviewer', function () {
    $owner = adminUser('Content Manager');
    $reviewer = adminUser('Editor');
    Article::factory()->published()->create(['owner_id' => $owner->id, 'reviewer_id' => $reviewer->id, 'unpublish_at' => now()->addDay()]);
    Article::factory()->published()->create(['owner_id' => $owner->id, 'unpublish_at' => now()->addDays(30)]);

    $this->artisan('content:expiring-reminders')->expectsOutputToContain('1 expiry reminder')->run();
    $this->artisan('content:expiring-reminders')->expectsOutputToContain('0 expiry reminder')->run();

    expect($owner->notifications()->count())->toBe(1)->and($reviewer->notifications()->count())->toBe(1)
        ->and($owner->notifications()->first()->data['title'])->toContain('unpublishes soon');
});
