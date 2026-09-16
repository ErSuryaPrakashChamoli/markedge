<?php

use App\Enums\PublishStatus;
use App\Models\Article;

it('includes only published records whose publish date has passed', function () {
    $this->freezeTime();

    $live = Article::factory()->published()->create();
    Article::factory()->create();
    Article::factory()->inReview()->create();
    Article::factory()->scheduled()->create();
    Article::factory()->create(['status' => PublishStatus::Published, 'published_at' => now()->addHour()]);
    Article::factory()->create(['status' => PublishStatus::Archived, 'published_at' => now()->subDay()]);

    expect(Article::published()->pluck('id')->all())->toBe([$live->id]);
});

it('lists scheduled records that are due for publishing', function () {
    $this->freezeTime();

    $due = Article::factory()->create(['status' => PublishStatus::Scheduled, 'published_at' => now()->subMinute()]);
    Article::factory()->scheduled()->create();

    expect(Article::dueForPublishing()->pluck('id')->all())->toBe([$due->id]);
});

it('publishes a draft with the current time', function () {
    $this->freezeSecond();

    $article = Article::factory()->create();

    $article->publish();

    expect($article->fresh())
        ->status->toBe(PublishStatus::Published)
        ->published_at->toEqual(now())
        ->isPublished()->toBeTrue();
});

it('keeps the original publish date when re-publishing', function () {
    $original = now()->subWeek()->startOfSecond();
    $article = Article::factory()->create(['status' => PublishStatus::Draft, 'published_at' => $original]);

    $article->publish();

    expect($article->fresh()->published_at)->toEqual($original);
});

it('reports a future-dated published record as not yet published', function () {
    $article = Article::factory()->create(['status' => PublishStatus::Published, 'published_at' => now()->addDay()]);

    expect($article->isPublished())->toBeFalse();
});
