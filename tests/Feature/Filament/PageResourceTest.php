<?php

use App\Enums\PublishStatus;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Support\PublishingFields;
use App\Models\Article;
use App\Models\Page;
use Livewire\Livewire;

it('creates a draft page with a generated slug and typed blocks', function () {
    $this->actingAs(adminUser('Website Manager'));

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'About Markedge',
            'template' => 'about',
            'blocks' => [
                ['type' => 'rich_text', 'data' => ['body' => '<p>Hello</p>', 'width' => 'narrow', 'is_enabled' => true, 'theme' => 'light']],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = Page::query()->where('slug', 'about-markedge')->first();

    expect($page)->not->toBeNull()
        ->and($page->status)->toBe(PublishStatus::Draft)
        ->and($page->blocks[0]['type'])->toBe('rich_text')
        ->and($page->creator?->id)->toBe(auth()->id());
});

it('lists pages and duplicates one as a draft copy', function () {
    $page = Page::factory()->create(['title' => 'Pricing', 'slug' => 'pricing']);
    $this->actingAs(adminUser('Website Manager'));

    Livewire::test(ListPages::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$page])
        ->callTableAction('replicate', $page)
        ->assertHasNoTableActionErrors();

    $copy = Page::query()->where('title', 'Pricing (copy)')->first();

    expect($copy)->not->toBeNull()
        ->and($copy->status)->toBe(PublishStatus::Draft)
        ->and($copy->slug)->not->toBe('pricing');
});

it('rejects a reserved slug', function () {
    $this->actingAs(adminUser());

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'Services listing', 'slug' => 'services', 'template' => 'default'])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

it('rejects a slug already used by another page', function () {
    Page::factory()->create(['slug' => 'careers']);
    $this->actingAs(adminUser());

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'Jobs', 'slug' => 'careers', 'template' => 'default'])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

it('keeps the slug when the title is edited', function () {
    $page = Page::factory()->create(['title' => 'Old title', 'slug' => 'old-title']);
    $this->actingAs(adminUser());

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm(['title' => 'New title'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->fresh())->title->toBe('New title')->slug->toBe('old-title');
});

it('changes the slug only when intentionally edited', function () {
    $page = Page::factory()->create(['slug' => 'old-title']);
    $this->actingAs(adminUser());

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm(['slug' => 'brand-new'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->fresh()->slug)->toBe('brand-new');
});

it('publishes a page through the header action', function () {
    $page = Page::factory()->create();
    $this->actingAs(adminUser('Website Manager'));

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction('publish')
        ->assertNotified();

    expect($page->fresh()->isPublished())->toBeTrue();
});

it('schedules a page for a future date', function () {
    $this->freezeSecond();
    $page = Page::factory()->create();
    $this->actingAs(adminUser());

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction('schedule', data: ['publish_at' => now()->addDays(2)->startOfMinute()->toDateTimeString()]);

    expect($page->fresh())
        ->status->toBe(PublishStatus::Scheduled)
        ->published_at->toEqual(now()->addDays(2)->startOfMinute());
});

it('hides the publish action from users without the publish permission', function () {
    $article = Article::factory()->create();
    $this->actingAs(adminUser('Content Manager'));

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->assertActionHidden('publish')
        ->assertActionVisible('submitForReview');
});

it('archives a published page', function () {
    $page = Page::factory()->published()->create();
    $this->actingAs(adminUser());

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->callAction('archive');

    expect($page->fresh()->status)->toBe(PublishStatus::Archived);
});

it('offers only draft and review statuses to users who cannot publish', function () {
    $this->actingAs(adminUser('Content Manager'));

    expect(array_keys(PublishingFields::statusOptions(Article::class)))->toBe(['draft', 'review']);

    $this->actingAs(adminUser('Editor'));

    expect(PublishingFields::statusOptions(Article::class))->toHaveCount(5);
});

it('accepts long SEO titles and descriptions without blocking', function () {
    $page = Page::factory()->create();
    $this->actingAs(adminUser());
    $title = str_repeat('Long title ', 20);
    $description = str_repeat('A long description. ', 30);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm(['seo' => ['title' => $title, 'description' => $description, 'robots_index' => true, 'robots_follow' => true, 'include_in_sitemap' => true]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->fresh()->seo)->title->toBe($title)->description->toBe($description);
});

it('rejects schema overrides that are not a JSON object', function () {
    $page = Page::factory()->create();
    $this->actingAs(adminUser());

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm(['seo' => ['schema_overrides' => '["list"]']])
        ->call('save')
        ->assertHasFormErrors(['seo.schema_overrides']);
});
