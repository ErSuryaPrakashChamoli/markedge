<?php

use App\Enums\SubscriberStatus;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Models\Setting;

it('subscribes a visitor and sends them back to the form they used', function () {
    $this->post('/newsletter', ['email' => 'Priya@Example.test ', 'consent' => '1', 'placement' => 'footer', 'source' => '/about'])
        ->assertRedirect(url('/about').'#newsletter-footer')
        ->assertSessionHas('newsletter.subscribed', 'footer');

    $subscriber = NewsletterSubscriber::query()->sole();

    expect($subscriber->email)->toBe('priya@example.test')
        ->and($subscriber->status)->toBe(SubscriberStatus::Subscribed)
        ->and($subscriber->source_path)->toBe('/about')
        ->and($subscriber->consent_text)->not->toBeEmpty()
        ->and($subscriber->consent_given_at)->not->toBeNull()
        ->and($subscriber->subscribed_at)->not->toBeNull();
});

it('requires a valid email and consent', function () {
    $this->post('/newsletter', ['email' => 'not-an-email', 'placement' => 'footer', 'source' => '/'])
        ->assertRedirect(url('/').'#newsletter-footer')
        ->assertSessionHasErrors(['email', 'consent'], null, 'newsletter');

    expect(NewsletterSubscriber::count())->toBe(0);
});

it('never redirects outside the site', function () {
    $this->post('/newsletter', ['email' => 'a@example.test', 'consent' => '1', 'source' => 'https://evil.test/phish'])
        ->assertRedirect(url('/').'#newsletter-block');
});

it('ignores bots that fill the hidden field', function () {
    $this->post('/newsletter', ['email' => 'bot@example.test', 'consent' => '1', 'website' => 'http://spam.test', 'source' => '/'])
        ->assertRedirect();

    expect(NewsletterSubscriber::count())->toBe(0);
});

it('keeps one record per address and resubscribes a returning reader', function () {
    NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'back@example.test']);

    $this->post('/newsletter', ['email' => 'back@example.test', 'consent' => '1', 'source' => '/'])->assertRedirect();
    $this->post('/newsletter', ['email' => 'back@example.test', 'consent' => '1', 'source' => '/'])->assertRedirect();

    $subscriber = NewsletterSubscriber::query()->sole();

    expect($subscriber->status)->toBe(SubscriberStatus::Subscribed)
        ->and($subscriber->unsubscribed_at)->toBeNull();
});

it('shows the footer newsletter band on every page unless switched off', function () {
    $page = Page::factory()->published()->create();

    $this->get('/'.$page->slug)->assertOk()->assertSee('id="newsletter-footer"', false)->assertSee('Subscribe');

    Setting::query()->create(['group' => 'newsletter', 'key' => 'newsletter.footer_enabled', 'type' => 'boolean', 'value' => false]);

    $this->get('/'.$page->slug)->assertOk()->assertDontSee('id="newsletter-footer"', false);
});

it('uses the newsletter block instead of the footer band when a page places one', function () {
    $page = Page::factory()->published()->create(['blocks' => [['type' => 'newsletter', 'data' => ['heading' => 'Get our monthly digest']]]]);

    $this->get('/'.$page->slug)
        ->assertOk()
        ->assertSee('Get our monthly digest')
        ->assertSee('id="newsletter-block"', false)
        ->assertDontSee('id="newsletter-footer"', false);
});

it('thanks the visitor on the form they submitted', function () {
    $page = Page::factory()->published()->create();

    $this->followingRedirects()
        ->post('/newsletter', ['email' => 'thanks@example.test', 'consent' => '1', 'placement' => 'footer', 'source' => '/'.$page->slug])
        ->assertOk()
        ->assertSee('Thanks for subscribing');
});
