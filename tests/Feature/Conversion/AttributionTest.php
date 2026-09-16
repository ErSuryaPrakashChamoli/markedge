<?php

use App\Attribution\Attribution;
use App\Models\Service;
use App\Models\Setting;
use App\Services\Cms\Settings;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Carries the attribution cookie from one response into the next request, like a browser.
 */
function attributionFrom(TestResponse $response): ?Attribution
{
    $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === 'mk_attr');

    if ($cookie === null) {
        return null;
    }

    $value = app('encrypter')->decrypt($cookie->getValue(), false);
    $value = CookieValuePrefix::remove($value);

    return Attribution::fromArray(json_decode($value, true));
}

function withAttribution(Attribution $attribution): TestCase
{
    return test()->withCookie('mk_attr', json_encode($attribution->toArray()));
}

it('sets a secure first-party cookie with the first touch on a campaign landing', function () {
    $service = Service::factory()->published()->create(['slug' => 'seo']);

    $response = $this->get('/services/seo?utm_source=Google&utm_medium=CPC&utm_campaign=Q4-Launch&utm_term=seo+agency&utm_content=ad1')->assertOk();
    $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === 'mk_attr');
    $attribution = attributionFrom($response);

    expect($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('lax')
        ->and($cookie->getExpiresTime())->toBeGreaterThan(now()->addDays(89)->getTimestamp())
        ->and($attribution->first->source)->toBe('google')
        ->and($attribution->first->medium)->toBe('cpc')
        ->and($attribution->first->campaign)->toBe('q4-launch')
        ->and($attribution->first->term)->toBe('seo agency')
        ->and($attribution->first->content)->toBe('ad1')
        ->and($attribution->first->landingPage)->toBe('/services/seo')
        ->and($attribution->last->source)->toBe('google')
        ->and($attribution->visits)->toBe(1)
        ->and(Str::isUuid($attribution->visitorId))->toBeTrue();
});

it('keeps the first touch immutable while updating the last touch', function () {
    $first = attributionFrom($this->get('/?utm_source=google&utm_medium=cpc'));
    $second = attributionFrom(withAttribution($first)->get('/products?utm_source=linkedin&utm_medium=social&utm_campaign=lms'));

    expect($second->visitorId)->toBe($first->visitorId)
        ->and($second->first->source)->toBe('google')
        ->and($second->first->landingPage)->toBe('/')
        ->and($second->last->source)->toBe('linkedin')
        ->and($second->last->campaign)->toBe('lms')
        ->and($second->last->landingPage)->toBe('/products')
        ->and($second->visits)->toBe(2);
});

it('does not overwrite the last touch on a direct revisit', function () {
    $first = attributionFrom($this->get('/?utm_source=google&utm_medium=cpc'));
    $response = withAttribution($first)->get('/services');
    $revisit = attributionFrom($response) ?? $first;

    expect($revisit->last->source)->toBe('google');
});

it('records direct traffic as unknown rather than inventing a source', function () {
    $attribution = attributionFrom($this->get('/services'));

    expect($attribution->first->source)->toBeNull()
        ->and($attribution->first->medium)->toBeNull()
        ->and($attribution->first->referrer)->toBeNull()
        ->and($attribution->first->landingPage)->toBe('/services');
});

it('derives source and medium from an external referrer and stores only the host', function () {
    $attribution = attributionFrom($this->withHeader('Referer', 'https://www.linkedin.com/feed/update/123?trk=abc')->get('/'));
    $unknown = attributionFrom($this->withHeader('Referer', 'https://partner.example.org/some/page?user=42')->get('/'));
    $internal = attributionFrom($this->withHeader('Referer', 'http://localhost/services')->get('/products'));

    expect($attribution->first->source)->toBe('linkedin')
        ->and($attribution->first->medium)->toBe('social')
        ->and($attribution->first->referrer)->toBe('www.linkedin.com')
        ->and($unknown->first->source)->toBe('partner.example.org')
        ->and($unknown->first->medium)->toBe('referral')
        ->and($unknown->first->referrer)->not->toContain('user=42')
        ->and($internal->first->source)->toBeNull();
});

it('bounds, cleans and never trusts malicious attribution input', function () {
    $long = str_repeat('a', 500);
    $attribution = attributionFrom($this->get('/?utm_source='.urlencode('<script>alert(1)</script>').'&utm_medium='.$long.'&utm_campaign[]=x&utm_term=%00%0d%0aInjected'));

    expect(mb_strlen($attribution->first->medium))->toBe(120)
        ->and($attribution->first->campaign)->toBeNull()
        ->and($attribution->first->term)->toBe('Injected')
        ->and($attribution->first->source)->toBe('<script>alert(1)</script>');

    $this->withCookie('mk_attr', '{"v":1,"id":"not-a-uuid","first":{"l":"javascript:alert(1)"}}')->get('/')->assertOk();
    $this->withCookie('mk_attr', 'garbage')->get('/')->assertOk();
    $this->withUnencryptedCookie('mk_attr', 'tampered-unencrypted')->get('/')->assertOk();
});

it('ignores admin, livewire, preview and non-GET requests', function () {
    $this->get('/admin/login')->assertOk()->assertCookieMissing('mk_attr');
    $this->get('/robots.txt')->assertOk()->assertCookieMissing('mk_attr');
    $this->get('/sitemap.xml')->assertOk()->assertCookieMissing('mk_attr');
});

it('holds the cookie back until consent when the privacy setting requires it', function () {
    Setting::query()->updateOrCreate(['key' => 'privacy.attribution_requires_consent'], ['group' => 'privacy', 'type' => 'boolean', 'value' => true]);
    app(Settings::class)->forget();

    $this->get('/?utm_source=google')->assertOk()->assertCookieMissing('mk_attr');
    $this->withCookie('mk_consent', '1')->get('/?utm_source=google')->assertOk()->assertCookie('mk_attr');
});

it('does not materially slow page requests', function () {
    Service::factory()->published()->create(['slug' => 'fast']);
    $this->get('/services/fast');

    DB::enableQueryLog();
    $this->get('/services/fast?utm_source=google&utm_medium=cpc')->assertOk();
    $withTracking = count(DB::getQueryLog());
    DB::flushQueryLog();
    $this->get('/services/fast')->assertOk();
    $without = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($withTracking)->toBe($without);
});
