<?php

use App\Enums\PublishStatus;
use App\Events\Content\ContentPublished;
use App\Events\Content\ContentSubmittedForReview;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Models\Article;
use App\Models\EditorialComment;
use App\Models\Page;
use App\Models\Service;
use App\Services\Cms\Publisher;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
});

it('walks draft → review → approved → published with an audit trail', function () {
    $editor = adminUser('Content Manager');
    $publisher = adminUser('Editor');
    $article = Article::factory()->create(['owner_id' => $editor->id, 'reviewer_id' => $publisher->id]);

    $this->actingAs($editor);
    app(Publisher::class)->submitForReview($article);
    expect($article->fresh()->status)->toBe(PublishStatus::Review)->and($article->fresh()->submitted_at)->not->toBeNull();

    app(Publisher::class)->approve($article);
    expect($article->fresh()->isApproved())->toBeTrue();

    $this->actingAs($publisher);
    app(Publisher::class)->publish($article);
    expect($article->fresh()->status)->toBe(PublishStatus::Published)->and($article->fresh()->isPublished())->toBeTrue();

    $events = Activity::query()->where('subject_id', $article->id)->pluck('event')->all();
    expect($events)->toContain('submitted for review', 'approved', 'published');
    $published = Activity::query()->where('subject_id', $article->id)->where('event', 'published')->first();
    expect($published->causer_id)->toBe($publisher->id)->and($published->properties['from'])->toBe('review')->and($published->properties['to'])->toBe('published');
});

it('stops an editor without publish permission from publishing, scheduling or archiving', function () {
    $this->actingAs(adminUser('Content Manager'));
    $article = Article::factory()->create();

    expect(fn () => app(Publisher::class)->publish($article))->toThrow(AuthorizationException::class)
        ->and(fn () => app(Publisher::class)->schedule($article, now()->addDay()))->toThrow(AuthorizationException::class)
        ->and(fn () => app(Publisher::class)->archive($article))->toThrow(AuthorizationException::class)
        ->and($article->fresh()->status)->toBe(PublishStatus::Draft);
});

it('lets a reviewer request changes with a reason the author sees', function () {
    $reviewer = adminUser('Content Manager');
    $article = Article::factory()->create(['status' => PublishStatus::Review, 'approved_at' => now()]);

    $this->actingAs($reviewer);
    expect(fn () => app(Publisher::class)->requestChanges($article, '   '))->toThrow(ValidationException::class);

    app(Publisher::class)->requestChanges($article, 'SEO description needs revision.');

    $article->refresh();
    $comment = EditorialComment::query()->sole();
    expect($article->status)->toBe(PublishStatus::Draft)
        ->and($article->approved_at)->toBeNull()
        ->and($comment->type->value)->toBe('change_request')
        ->and($comment->body)->toBe('SEO description needs revision.')
        ->and($comment->user_id)->toBe($reviewer->id)
        ->and(Activity::query()->where('event', 'changes requested')->first()->properties['reason'])->toBe('SEO description needs revision.');

    $this->actingAs(adminUser('Sales'));
    expect(fn () => app(Publisher::class)->requestChanges($article, 'x'))->toThrow(AuthorizationException::class);
});

it('rejects illegal transitions and duplicate publication', function () {
    $this->actingAs(adminUser('Super Admin'));
    $service = Service::factory()->published()->create();

    expect(fn () => app(Publisher::class)->publish($service))->toThrow(ValidationException::class, 'already published')
        ->and(fn () => app(Publisher::class)->submitForReview($service))->toThrow(ValidationException::class, 'Cannot move');

    $archived = Service::factory()->create(['status' => PublishStatus::Archived]);
    expect(fn () => app(Publisher::class)->publish($archived))->toThrow(ValidationException::class);
    app(Publisher::class)->restore($archived);
    expect($archived->fresh()->status)->toBe(PublishStatus::Draft);
});

it('clears approval when content changes after approval', function () {
    $article = Article::factory()->create(['status' => PublishStatus::Review, 'approved_at' => now(), 'approved_by' => adminUser()->id]);

    $article->update(['title' => 'Edited after approval']);

    expect($article->fresh()->approved_at)->toBeNull();
});

it('assigns owner and reviewer without touching content, URL or status', function () {
    $manager = adminUser('Website Manager');
    $owner = adminUser('Content Manager');
    $page = Page::factory()->published()->create(['slug' => 'about']);
    $before = [$page->slug, $page->status, $page->title, $page->updated_at];

    $this->actingAs($manager);
    app(Publisher::class)->assign($page, $owner->id, $manager->id);

    $page->refresh();
    expect($page->owner_id)->toBe($owner->id)->and($page->reviewer_id)->toBe($manager->id)
        ->and([$page->slug, $page->status, $page->title, $page->updated_at])->toEqual($before)
        ->and(Activity::query()->where('event', 'assigned')->exists())->toBeTrue();

    $this->actingAs(adminUser('Sales'));
    expect(fn () => app(Publisher::class)->assign($page, null, null))->toThrow(AuthorizationException::class);
});

it('dispatches workflow events for downstream systems', function () {
    Event::fake([ContentSubmittedForReview::class, ContentPublished::class]);
    $this->actingAs(adminUser('Super Admin'));
    $article = Article::factory()->create();

    app(Publisher::class)->submitForReview($article);
    app(Publisher::class)->publish($article);

    Event::assertDispatched(ContentSubmittedForReview::class, fn ($e) => $e->type === 'article' && $e->id === $article->id);
    Event::assertDispatched(ContentPublished::class);
});

it('runs bulk workflow actions per record with authorization and no silent failures', function () {
    $this->actingAs(adminUser('Content Manager'));
    $drafts = Article::factory()->count(3)->create();

    Livewire::test(ListArticles::class)
        ->selectTableRecords($drafts)
        ->callAction(TestAction::make('submitForReviewSelected')->table()->bulk())
        ->assertNotified();

    expect(Article::query()->where('status', PublishStatus::Review)->count())->toBe(3);

    Livewire::test(ListArticles::class)
        ->selectTableRecords($drafts)
        ->callAction(TestAction::make('publishSelected')->table()->bulk())
        ->assertNotified();

    expect(Article::query()->where('status', PublishStatus::Published)->count())->toBe(0);
});
