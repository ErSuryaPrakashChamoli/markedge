<?php

use App\Enums\ArticleType;
use App\Enums\SocialPlatform;
use App\Enums\SubscriberStatus;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\NewsletterSubscribers\Pages\ManageNewsletterSubscribers;
use App\Filament\Resources\SocialVideos\Pages\CreateSocialVideo;
use App\Filament\Resources\SocialVideos\Pages\ListSocialVideos;
use App\Models\Article;
use App\Models\NewsletterSubscriber;
use App\Models\SocialVideo;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('lets a content manager add a social video by pasting its link', function () {
    $this->actingAs(adminUser('Content Manager'));

    Livewire::test(CreateSocialVideo::class)
        ->fillForm(['url' => 'https://www.instagram.com/reel/C1a2B3c4D5e/'])
        ->assertSchemaStateSet(['platform' => SocialPlatform::Instagram, 'is_vertical' => true])
        ->fillForm(['title' => 'Our new office tour'])
        ->call('create')
        ->assertHasNoFormErrors();

    $video = SocialVideo::query()->sole();

    expect($video->platform)->toBe(SocialPlatform::Instagram)
        ->and($video->is_visible)->toBeTrue()
        ->and($video->embedUrl())->toBe('https://www.instagram.com/reel/C1a2B3c4D5e/embed/');
});

it('lists social videos for editors', function () {
    $video = SocialVideo::factory()->linkedIn()->create();
    $this->actingAs(adminUser('Editor'));

    Livewire::test(ListSocialVideos::class)->assertCanSeeTableRecords([$video]);
});

it('keeps social videos and subscribers away from roles without access', function () {
    $this->actingAs(adminUser('Sales'));

    $this->get(ListSocialVideos::getUrl())->assertForbidden();
    $this->get(ManageNewsletterSubscribers::getUrl())->assertForbidden();
});

it('stores the article type chosen in the editor', function () {
    $this->actingAs(adminUser('Editor'));

    Livewire::test(CreateArticle::class)
        ->fillForm(['title' => 'Markedge joins the cloud partner programme', 'type' => ArticleType::News->value, 'excerpt' => 'Short update.', 'body' => '<p>Update body.</p>'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Article::query()->sole()->type)->toBe(ArticleType::News);
});

it('lets marketing review, unsubscribe and export newsletter subscribers', function () {
    $subscribed = NewsletterSubscriber::factory()->create(['email' => 'reader@example.test']);
    $this->actingAs(adminUser('Marketing Manager'));

    Livewire::test(ManageNewsletterSubscribers::class)
        ->assertCanSeeTableRecords([$subscribed])
        ->callAction(TestAction::make('unsubscribe')->table($subscribed))
        ->assertHasNoActionErrors()
        ->selectTableRecords([$subscribed])
        ->callAction(TestAction::make('exportCsv')->table()->bulk())
        ->assertFileDownloaded('newsletter-subscribers-'.now()->format('Y-m-d').'.csv');

    expect($subscribed->fresh()->status)->toBe(SubscriberStatus::Unsubscribed)
        ->and($subscribed->fresh()->unsubscribed_at)->not->toBeNull();
});
