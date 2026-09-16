<?php

use App\Enums\PublishStatus;
use App\Filament\Pages\EditorialDesk;
use App\Models\Article;
use App\Models\EditorialComment;
use App\Services\Cms\Publisher;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
});

it('lets editors comment, reviewers resolve, and never leaks comments publicly', function () {
    $editor = adminUser('Content Manager');
    $article = Article::factory()->published()->create(['slug' => 'public-article']);

    $this->actingAs($editor);
    Livewire::test(EditorialDesk::class, ['type' => 'article', 'record' => $article->id])
        ->set('commentBody', 'Internal note: SECRET-NOTE-123')
        ->call('addComment')->assertHasNoErrors()->assertSee('SECRET-NOTE-123');

    $comment = EditorialComment::query()->sole();
    expect($comment->user_id)->toBe($editor->id)->and($comment->isResolved())->toBeFalse();

    Livewire::test(EditorialDesk::class, ['type' => 'article', 'record' => $article->id])->call('resolveComment', $comment->id);
    expect($comment->fresh()->isResolved())->toBeTrue();

    $html = $this->get('/insights/public-article')->assertOk()->getContent();
    expect($html)->not->toContain('SECRET-NOTE-123');
    $this->get('/search?q=SECRET-NOTE')->assertSee('No results');

    $this->actingAs(adminUser('Sales'));
    $this->get(EditorialDesk::getUrl(['type' => 'article', 'record' => $article->id]))->assertForbidden();
});

it('notifies the reviewer on submission, the owner on changes requested and approval, never the actor', function () {
    $owner = adminUser('Content Manager');
    $reviewer = adminUser('Editor');
    $article = Article::factory()->create(['owner_id' => $owner->id, 'reviewer_id' => $reviewer->id]);

    $this->actingAs($owner);
    app(Publisher::class)->submitForReview($article);
    expect($reviewer->notifications()->count())->toBe(1)->and($owner->notifications()->count())->toBe(0)
        ->and($reviewer->notifications()->first()->data['title'])->toContain('awaiting your review');

    $this->actingAs($reviewer);
    app(Publisher::class)->requestChanges($article, 'Tighten the intro.');
    expect($owner->notifications()->count())->toBe(1)->and($owner->notifications()->first()->data['body'])->toBe('Tighten the intro.');

    $article->forceFill(['status' => PublishStatus::Review])->saveQuietly();
    app(Publisher::class)->approve($article);
    expect($owner->notifications()->count())->toBe(2);

    $article->update(['title' => 'Minor edit']);
    expect($owner->notifications()->count())->toBe(2);
});

it('still publishes when notification delivery fails', function () {
    $owner = adminUser('Content Manager');
    $article = Article::factory()->create(['owner_id' => $owner->id, 'status' => PublishStatus::Review]);
    $this->actingAs(adminUser('Editor'));
    DB::statement('DROP TABLE notifications');

    app(Publisher::class)->publish($article);

    expect($article->fresh()->status)->toBe(PublishStatus::Published);
});
