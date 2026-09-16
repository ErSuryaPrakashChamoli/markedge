<?php

use App\Enums\PublishStatus;
use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\LandingPage;
use App\Models\Page;
use App\Services\Cms\Publisher;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

it('moves a draft to review and records the transition', function () {
    $article = Article::factory()->create();

    app(Publisher::class)->submitForReview($article);

    expect($article->fresh()->status)->toBe(PublishStatus::Review)
        ->and(Activity::query()->where('event', 'submitted for review')->where('subject_id', $article->id)->exists())->toBeTrue();
});

it('publishes with the current time and keeps a past publish date', function () {
    $this->freezeSecond();
    $fresh = Article::factory()->create();
    $dated = Article::factory()->create(['published_at' => now()->subWeek()]);

    app(Publisher::class)->publish($fresh);
    app(Publisher::class)->publish($dated);

    expect($fresh->fresh()->published_at)->toEqual(now())
        ->and($dated->fresh()->published_at)->toEqual(now()->subWeek());
});

it('refuses to publish a case study without a challenge and solution', function () {
    $caseStudy = CaseStudy::factory()->create(['challenge' => null, 'solution' => '<p>ok</p>']);

    app(Publisher::class)->publish($caseStudy);
})->throws(ValidationException::class);

it('refuses to publish a landing page with neither form nor CTA', function () {
    $landingPage = LandingPage::factory()->create();

    app(Publisher::class)->publish($landingPage);
})->throws(ValidationException::class);

it('refuses to publish a page with an unknown block type', function () {
    $page = Page::factory()->create(['blocks' => [['type' => 'raw_html', 'data' => []]]]);

    expect(app(Publisher::class)->checklist($page)->isNotEmpty())->toBeTrue();

    app(Publisher::class)->publish($page);
})->throws(ValidationException::class);

it('schedules future dates and publishes past ones immediately', function () {
    $this->freezeSecond();
    $future = Page::factory()->create();
    $past = Page::factory()->create();

    app(Publisher::class)->schedule($future, now()->addDay());
    app(Publisher::class)->schedule($past, now()->subMinute());

    expect($future->fresh()->status)->toBe(PublishStatus::Scheduled)
        ->and($past->fresh()->status)->toBe(PublishStatus::Published);
});

it('publishes due scheduled content and leaves future content alone', function () {
    $this->freezeSecond();
    $due = Article::factory()->create(['status' => PublishStatus::Scheduled, 'published_at' => now()->subMinute()]);
    $later = Article::factory()->scheduled()->create();

    $count = app(Publisher::class)->publishDue();

    expect($count)->toBe(1)
        ->and($due->fresh()->status)->toBe(PublishStatus::Published)
        ->and($later->fresh()->status)->toBe(PublishStatus::Scheduled);
});

it('runs the scheduled publishing command', function () {
    Article::factory()->create(['status' => PublishStatus::Scheduled, 'published_at' => now()->subMinute()]);

    $this->artisan('content:publish-scheduled')->expectsOutputToContain('Published 1')->assertSuccessful();
});

it('unpublishes back to draft and archives', function () {
    $page = Page::factory()->published()->create();

    app(Publisher::class)->unpublish($page);
    expect($page->fresh()->status)->toBe(PublishStatus::Draft);

    app(Publisher::class)->archive($page);
    expect($page->fresh()->status)->toBe(PublishStatus::Archived);
});
