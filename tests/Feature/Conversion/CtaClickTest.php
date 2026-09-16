<?php

use App\Attribution\Attribution;
use App\Attribution\Touch;
use App\Enums\CtaAction;
use App\Models\ConversionEvent;
use App\Models\Cta;
use App\Models\CtaClick;
use App\Models\Service;
use App\Models\Setting;
use App\Services\Cms\ContentVersion;
use App\Services\Cms\Settings;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Schema;

function trackedAttribution(): Attribution
{
    $attribution = Attribution::fresh();
    $attribution->first = new Touch('google', 'cpc', 'q4', null, null, null, '/', now()->toIso8601String());
    $attribution->last = $attribution->first;

    return $attribution;
}

it('records an internal CTA click and redirects to the CTA destination', function () {
    $cta = Cta::factory()->create(['key' => 'request-quote', 'primary_action' => CtaAction::Url, 'primary_value' => '/request-quote']);

    $response = $this->withCookie('mk_attr', json_encode(trackedAttribution()->toArray()))->get('/go/request-quote?p=/services/seo');

    $response->assertRedirect('/request-quote')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $click = CtaClick::query()->firstOrFail();

    expect($click->cta_id)->toBe($cta->id)
        ->and($click->path)->toBe('/services/seo')
        ->and($click->target)->toBe('/request-quote')
        ->and($click->source)->toBe('google')
        ->and($click->campaign)->toBe('q4')
        ->and($click->visitor_id)->not->toBeNull()
        ->and($click->created_at)->not->toBeNull()
        ->and($cta->fresh()->click_count)->toBe(1)
        ->and(ConversionEvent::query()->where('type', 'cta_clicked')->count())->toBe(1);
});

it('redirects phone, email and WhatsApp CTAs safely', function () {
    Setting::query()->updateOrCreate(['key' => 'contact.phone'], ['group' => 'contact', 'type' => 'phone', 'value' => '+91 98765 43210']);
    Setting::query()->updateOrCreate(['key' => 'contact.email'], ['group' => 'contact', 'type' => 'email', 'value' => 'hello@example.test']);
    Setting::query()->updateOrCreate(['key' => 'contact.whatsapp'], ['group' => 'contact', 'type' => 'phone', 'value' => '+91 98765 43210']);
    app(Settings::class)->forget();
    Cta::factory()->create(['key' => 'call', 'primary_action' => CtaAction::Phone]);
    Cta::factory()->create(['key' => 'mail', 'primary_action' => CtaAction::Email]);
    Cta::factory()->create(['key' => 'chat', 'primary_action' => CtaAction::Whatsapp, 'whatsapp_message' => 'Hi about {entity}']);

    $this->get('/go/call')->assertRedirect('tel:+919876543210');
    $this->get('/go/mail')->assertRedirect('mailto:hello@example.test');
    $this->get('/go/chat?e=SEO')->assertRedirect('https://wa.me/919876543210?text=Hi%20about%20SEO');
    expect(CtaClick::query()->pluck('action')->all())->toBe(['phone', 'email', 'whatsapp']);
});

it('refuses unsafe, unknown, inactive and non-allow-listed destinations', function () {
    Cta::factory()->create(['key' => 'js', 'primary_action' => CtaAction::Url, 'primary_value' => 'javascript:alert(1)']);
    Cta::factory()->create(['key' => 'data', 'primary_action' => CtaAction::Url, 'primary_value' => 'data:text/html,hi']);
    Cta::factory()->create(['key' => 'proto', 'primary_action' => CtaAction::Url, 'primary_value' => '//evil.example/x']);
    Cta::factory()->create(['key' => 'ext', 'primary_action' => CtaAction::Url, 'primary_value' => 'https://evil.example/x']);
    Cta::factory()->create(['key' => 'off', 'is_active' => false, 'primary_action' => CtaAction::Url, 'primary_value' => '/contact']);
    Cta::factory()->create(['key' => 'empty', 'primary_action' => CtaAction::Url, 'primary_value' => null]);

    foreach (['js', 'data', 'proto', 'ext', 'off', 'empty', 'missing', '../x', 'ok/evil'] as $key) {
        $this->get('/go/'.$key)->assertNotFound();
    }

    $this->get('/go/js/tertiary')->assertNotFound();
    expect(CtaClick::count())->toBe(0);
});

it('allows explicitly allow-listed external hosts and the site itself', function () {
    config()->set('markedge.cta.allowed_external_hosts', ['calendly.com']);
    Cta::factory()->create(['key' => 'book', 'primary_action' => CtaAction::Url, 'primary_value' => 'https://calendly.com/markedge/intro']);
    Cta::factory()->create(['key' => 'self', 'primary_action' => CtaAction::Url, 'primary_value' => 'http://localhost/contact']);

    $this->get('/go/book')->assertRedirect('https://calendly.com/markedge/intro');
    $this->get('/go/self')->assertRedirect('http://localhost/contact');
});

it('ignores a user-supplied destination and validates the page parameter', function () {
    Cta::factory()->create(['key' => 'quote', 'primary_action' => CtaAction::Url, 'primary_value' => '/request-quote']);

    $this->get('/go/quote?to=https://evil.example&p=https://evil.example/page')->assertRedirect('/request-quote');
    $this->get('/go/quote?p[]=x')->assertRedirect('/request-quote');

    expect(CtaClick::query()->pluck('path')->all())->toBe([null, null]);
});

it('still redirects when click recording fails', function () {
    Cta::factory()->create(['key' => 'quote', 'primary_action' => CtaAction::Url, 'primary_value' => '/request-quote']);
    Schema::drop('cta_clicks');

    $this->get('/go/quote')->assertRedirect('/request-quote');
});

it('renders tracked links with nofollow across the site and keeps /go out of robots', function () {
    Cta::factory()->create(['key' => 'talk-to-us', 'primary_action' => CtaAction::Url, 'primary_value' => '/contact']);
    Setting::query()->updateOrCreate(['key' => 'cta.header'], ['group' => 'cta', 'type' => 'text', 'value' => 'talk-to-us']);
    app(Settings::class)->forget();
    Service::factory()->published()->create(['slug' => 'seo']);

    $this->get('/services/seo')->assertOk()
        ->assertSee('href="http://localhost/go/talk-to-us?p=%2Fservices%2Fseo"', false)
        ->assertSee('rel="nofollow"', false);

    $this->get('/robots.txt')->assertSee('Disallow: /go');
});

it('records a click with the secondary slot and credits the CTA on the next lead', function () {
    $cta = Cta::factory()->create(['key' => 'demo', 'primary_action' => CtaAction::Url, 'primary_value' => '/request-demo', 'secondary_label' => 'Call', 'secondary_action' => CtaAction::Url, 'secondary_value' => '/contact']);

    $response = $this->get('/go/demo/secondary')->assertRedirect('/contact');
    $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === 'mk_attr');
    $attribution = Attribution::fromArray(json_decode(CookieValuePrefix::remove(app('encrypter')->decrypt($cookie->getValue(), false)), true));

    expect($attribution->lastCtaId)->toBe($cta->id)->and($attribution->creditedCtaId())->toBe($cta->id)
        ->and(CtaClick::query()->firstOrFail()->action)->toBe('url');
});

it('does not bump the content version when a click is recorded', function () {
    Cta::factory()->create(['key' => 'quote', 'primary_action' => CtaAction::Url, 'primary_value' => '/request-quote']);
    $version = app(ContentVersion::class)->current();

    $this->get('/go/quote')->assertRedirect('/request-quote');

    expect(app(ContentVersion::class)->current())->toBe($version);
});
