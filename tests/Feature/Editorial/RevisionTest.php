<?php

use App\Editorial\RevisionDiff;
use App\Editorial\RevisionManager;
use App\Enums\PublishStatus;
use App\Filament\Pages\EditorialDesk;
use App\Models\Article;
use App\Models\ContentRevision;
use App\Models\EditorialComment;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\SearchEntry;
use App\Models\Service;
use App\Models\Tag;
use App\Services\Cms\Publisher;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
    config()->set('markedge.editorial.revision_coalesce_seconds', 0);
});

it('creates deterministic versions on content changes only', function () {
    $this->actingAs(adminUser());
    $page = Page::factory()->create(['title' => 'One']);
    expect($page->revisions()->pluck('version')->all())->toBe([1]);

    $page->update(['title' => 'Two']);
    $page->update(['status' => PublishStatus::Review]);
    $page->update(['owner_id' => adminUser()->id]);
    $page->update(['title' => 'Three']);

    expect($page->revisions()->pluck('version')->all())->toBe([3, 2, 1])
        ->and($page->revisions()->first()->snapshot['attributes']['title'])->toBe('Three')
        ->and($page->revisions()->first()->created_by)->toBe(auth()->id());
});

it('folds consecutive saves by the same user into one version within the window', function () {
    config()->set('markedge.editorial.revision_coalesce_seconds', 30);
    $this->actingAs(adminUser());
    $article = Article::factory()->create(['title' => 'A']);
    $article->update(['title' => 'B']);
    $article->seo()->create(['description' => 'Snippet']);
    $article->touch();
    app(RevisionManager::class)->capture($article);

    expect($article->revisions()->count())->toBe(1)
        ->and($article->revisions()->first()->snapshot['seo']['description'])->toBe('Snippet');
});

it('snapshots SEO, media references and relation ids and compares versions readably', function () {
    $this->actingAs(adminUser());
    $tag = Tag::factory()->create();
    $article = Article::factory()->create(['title' => 'Original', 'excerpt' => 'First excerpt']);
    $article->seo()->create(['title' => 'SEO one', 'robots_index' => true]);
    $article->tags()->attach($tag);
    app(RevisionManager::class)->capture($article, 'Initial');

    $article->update(['title' => 'Changed', 'excerpt' => 'Second excerpt']);
    $article->seo->update(['title' => 'SEO two']);
    $article->tags()->detach();
    app(RevisionManager::class)->capture($article, 'Edited');

    $from = $article->revisions()->where('reason', 'Initial')->firstOrFail();
    $to = $article->revisions()->where('reason', 'Edited')->firstOrFail();
    $changes = collect(app(RevisionDiff::class)->between($from, $to));

    expect($from->snapshot['relations']['tags'])->toBe([$tag->id])
        ->and($from->snapshot['media'])->toBe([])
        ->and($changes->firstWhere('field', 'Title'))->toMatchArray(['from' => 'Original', 'to' => 'Changed'])
        ->and($changes->where('section', 'SEO')->firstWhere('field', 'Title'))->toMatchArray(['from' => 'SEO one', 'to' => 'SEO two'])
        ->and($changes->firstWhere('section', 'Relations')['from'])->toContain('1 item(s)');
});

it('restores an earlier version as a new version, returns live content to draft and syncs search', function () {
    $this->actingAs(adminUser());
    $service = Service::factory()->published()->create(['name' => 'Cloud v1', 'slug' => 'cloud']);
    $v1 = $service->revisions()->first();
    $service->update(['name' => 'Cloud v2']);
    $service->update(['name' => 'Cloud v3']);
    expect(SearchEntry::query()->where('searchable_id', $service->id)->value('title'))->toBe('Cloud v3');

    $new = app(RevisionManager::class)->restore($v1, $service, 3);

    $service->refresh();
    expect($new->version)->toBe(4)
        ->and($new->reason)->toBe('Restored from v1')
        ->and($service->name)->toBe('Cloud v1')
        ->and($service->status)->toBe(PublishStatus::Draft)
        ->and($service->revisions()->count())->toBe(4)
        ->and(SearchEntry::query()->where('searchable_id', $service->id)->exists())->toBeFalse();

    $this->get('/search?q=cloud')->assertSee('No results');

    app(Publisher::class)->publish($service);
    expect(SearchEntry::query()->where('searchable_id', $service->id)->value('title'))->toBe('Cloud v1');
    $this->get('/search?q=cloud')->assertSee('Cloud v1');
});

it('refuses stale restores, foreign versions and malformed or unknown-block snapshots', function () {
    $this->actingAs(adminUser());
    $page = Page::factory()->create(['title' => 'Page A']);
    $other = Page::factory()->create(['title' => 'Page B']);
    $foreign = $other->revisions()->first();

    expect(fn () => app(RevisionManager::class)->restore($foreign, $page, 1))->toThrow(ValidationException::class, 'does not belong');

    $page->update(['title' => 'Page A2']);
    $v1 = $page->revisions()->where('version', 1)->first();
    expect(fn () => app(RevisionManager::class)->restore($v1, $page, 1))->toThrow(ValidationException::class, 'Newer edits');

    $bad = ContentRevision::factory()->create(['revisionable_type' => 'page', 'revisionable_id' => $page->id, 'version' => 99, 'snapshot' => ['attributes' => ['status' => 'published', 'title' => 'x']]]);
    expect(fn () => app(RevisionManager::class)->restore($bad, $page, 99))->toThrow(ValidationException::class, 'cannot be restored');

    $unknown = ContentRevision::factory()->create(['revisionable_type' => 'page', 'revisionable_id' => $page->id, 'version' => 100, 'snapshot' => ['attributes' => ['blocks' => [['type' => 'evil_script', 'data' => []]]]]]);
    expect(fn () => app(RevisionManager::class)->restore($unknown, $page, 100))->toThrow(ValidationException::class, 'unknown type');

    $schema = ContentRevision::factory()->create(['revisionable_type' => 'page', 'revisionable_id' => $page->id, 'version' => 101, 'snapshot' => ['attributes' => ['title' => 'ok'], 'seo' => ['schema_overrides' => ['<script>'], 'canonical_url' => 'javascript:alert(1)']]]);
    expect(fn () => app(RevisionManager::class)->restore($schema, $page, 101))->toThrow(ValidationException::class);

    expect($page->fresh()->title)->toBe('Page A2');
});

it('creates a redirect when a restored version changes a published slug', function () {
    $this->actingAs(adminUser());
    $service = Service::factory()->published()->create(['slug' => 'old-slug']);
    $service->update(['slug' => 'new-slug']);
    expect(Redirect::query()->where('from_path', '/services/old-slug')->exists())->toBeTrue();

    $v1 = $service->revisions()->where('version', 1)->first();
    app(RevisionManager::class)->restore($v1, $service, 2);

    expect($service->fresh()->slug)->toBe('old-slug')
        ->and(Redirect::query()->where('from_path', '/services/new-slug')->where('to_url', '/services/old-slug')->exists())->toBeTrue()
        ->and(Redirect::query()->where('from_path', '/services/old-slug')->exists())->toBeFalse();
});

it('shows version history on the editorial desk and blocks unauthorised restores', function () {
    $this->actingAs(adminUser('Content Manager'));
    $article = Article::factory()->create(['title' => 'Draft title']);
    $article->update(['title' => 'Second title']);

    Livewire::test(EditorialDesk::class, ['type' => 'article', 'record' => $article->id])
        ->assertOk()->assertSee('v2')->assertSee('current')
        ->callAction(TestAction::make('restore')->arguments(['revision' => $article->revisions()->where('version', 1)->value('id'), 'version' => 1, 'latest' => 2]))
        ->assertNotified();

    expect($article->fresh()->title)->toBe('Draft title')->and($article->revisions()->count())->toBe(3);

    $this->actingAs(adminUser('SEO Manager'));
    Livewire::test(EditorialDesk::class, ['type' => 'article', 'record' => $article->id])
        ->assertOk()->assertActionHidden(TestAction::make('restore')->arguments(['revision' => 1, 'version' => 1, 'latest' => 3]));

    expect(fn () => app(RevisionManager::class)->restore($article->revisions()->where('version', 1)->first(), $article, 3))->toThrow(AuthorizationException::class);

    $this->actingAs(adminUser('Sales'));
    $this->get(EditorialDesk::getUrl(['type' => 'article', 'record' => $article->id]))->assertForbidden();
    $this->get(EditorialDesk::getUrl(['type' => 'article', 'record' => 999999]))->assertNotFound();
});

it('removes revisions and comments only on force delete', function () {
    $this->actingAs(adminUser());
    $article = Article::factory()->create();
    $article->editorialComments()->create(['user_id' => auth()->id(), 'body' => 'note']);

    $article->delete();
    expect(ContentRevision::count())->toBe(1);

    $article->forceDelete();
    expect(ContentRevision::count())->toBe(0)->and(EditorialComment::count())->toBe(0);
});
