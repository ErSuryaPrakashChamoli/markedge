<?php

use App\Enums\CampaignStatus;
use App\Enums\CtaAction;
use App\Health\HealthChecks;
use App\Models\Campaign;
use App\Models\Cta;
use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;
use App\Services\Cms\PreviewLink;
use App\Services\Cms\Settings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

it('sends baseline security headers on every response', function () {
    $response = $this->get('/')->assertOk();

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeaderMissing('Strict-Transport-Security');

    expect($response->headers->get('Permissions-Policy'))->toContain('camera=()');
    expect($response->headers->get('X-Request-Id'))->toMatch('/^[0-9a-f-]{36}$/');

    $this->withHeader('X-Request-Id', 'cdn-abc-12345678')->get('/')->assertHeader('X-Request-Id', 'cdn-abc-12345678');
    expect($this->withHeader('X-Request-Id', 'bad value!')->get('/')->headers->get('X-Request-Id'))->toMatch('/^[0-9a-f-]{36}$/');
});

it('applies a nonce-based content security policy to public pages and none to admin surfaces', function () {
    $service = Service::factory()->published()->create(['slug' => 'seo']);

    $response = $this->get('/services/seo')->assertOk();
    $csp = (string) $response->headers->get('Content-Security-Policy');

    preg_match("/'nonce-([A-Za-z0-9+\/=_-]+)'/", $csp, $m);
    expect($m[1] ?? null)->not->toBeNull()
        ->and($csp)->toContain("object-src 'none'")->toContain("base-uri 'self'")->toContain("frame-ancestors 'self'")
        ->toContain('https://www.youtube-nocookie.com')
        ->and($response->getContent())->toContain('nonce="'.$m[1].'"');

    // Every script tag on the page carries the nonce (Vite, Livewire config) except JSON-LD data blocks.
    preg_match_all('/<script(?![^>]*application\/ld\+json)[^>]*>/', $response->getContent(), $scripts);
    foreach ($scripts[0] as $tag) {
        expect($tag)->toContain('nonce="'.$m[1].'"');
    }

    $second = $this->get('/services/seo');
    preg_match("/'nonce-([^']+)'/", (string) $second->headers->get('Content-Security-Policy'), $m2);
    expect($m2[1])->not->toBe($m[1]);

    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
    $this->actingAs(adminUser());
    $admin = $this->get('/admin')->assertOk()->assertHeaderMissing('Content-Security-Policy');
    expect($admin->headers->get('Cache-Control'))->toContain('no-store');
});

it('sends HSTS only on secure requests when enabled', function () {
    config()->set('markedge.security.hsts', true);

    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('never lets private or personalised responses be shared-cached', function () {
    Service::factory()->published()->create(['slug' => 'seo']);
    $cta = Cta::factory()->create(['key' => 'quote', 'primary_action' => CtaAction::Url, 'primary_value' => '/request-quote']);

    $this->get('/services/seo')->assertHeader('Cache-Control', 'no-cache, private');
    $this->get('/search?q=seo')->assertHeader('Cache-Control', 'no-cache, private');
    $this->get('/go/quote')->assertRedirect('/request-quote')->assertHeader('Cache-Control', 'no-store, private');
    $this->get('/health')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->get('/sitemap.xml')->assertOk()->assertHeader('Cache-Control', 'max-age=3600, public');

    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
    $this->actingAs(adminUser());
    expect($this->get('/admin/leads')->headers->get('Cache-Control'))->toContain('no-store');
    $this->get('/services/seo')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
});

it('keeps campaign personalisation per visitor even across repeated requests', function () {
    Cta::factory()->create(['key' => 'start-conversation', 'primary_label' => 'Default CTA']);
    $campaignCta = Cta::factory()->create(['key' => 'campaign-cta', 'primary_label' => 'Campaign CTA', 'primary_value' => '/request-quote']);
    Setting::query()->updateOrCreate(['key' => 'cta.default_service'], ['group' => 'cta', 'type' => 'text', 'value' => 'start-conversation']);
    app(Settings::class)->forget();
    Campaign::factory()->create(['utm_campaign' => 'q4', 'status' => CampaignStatus::Active, 'cta_id' => $campaignCta->id, 'personalize_cta' => true]);
    Service::factory()->published()->create(['slug' => 'seo']);

    $this->get('/services/seo?utm_campaign=q4')->assertSee('Campaign CTA');
    $this->get('/services/seo')->assertSee('Default CTA')->assertDontSee('Campaign CTA');
    $this->get('/services/seo?utm_campaign=q4')->assertSee('Campaign CTA');
    $this->get('/services/seo')->assertDontSee('Campaign CTA');
});

it('exposes liveness and readiness without leaking internals', function () {
    $this->get('/health')->assertOk()->assertExactJson(['status' => 'ok']);

    $ready = $this->get('/health/ready')->assertOk()->assertJson(['status' => 'ok', 'checks' => ['database' => 'ok', 'cache' => 'ok', 'queue' => 'ok']]);
    $body = $ready->getContent();

    foreach ([config('database.connections.sqlite.database'), 'sqlite', 'mysql', config('app.key'), 'DB_', 'vendor/', 'Exception'] as $secret) {
        expect($body)->not->toContain((string) $secret);
    }

    $this->get('/robots.txt')->assertSee('Disallow: /health');
    expect($this->get('/health')->headers->has('mk_attr'))->toBeFalse();
    $this->get('/health')->assertCookieMissing('mk_attr');
});

it('reports degraded readiness when a dependency fails', function () {
    $this->partialMock(HealthChecks::class, fn ($mock) => $mock->shouldReceive('cache')->andThrow(new RuntimeException('redis down at 10.0.0.5')));

    $response = $this->get('/health/ready')->assertStatus(503)->assertJson(['status' => 'degraded', 'checks' => ['cache' => 'fail', 'database' => 'ok']]);

    expect($response->getContent())->not->toContain('10.0.0.5')->not->toContain('redis down');
});

it('renders safe error pages without stack traces when debug is off', function () {
    config()->set('app.debug', false);
    Route::get('/__boom', fn () => throw new RuntimeException('secret internal detail /var/www/app.php'));

    $response = $this->get('/__boom')->assertStatus(500);

    expect($response->getContent())->not->toContain('secret internal detail')->not->toContain('RuntimeException')
        ->and($response->getContent())->toContain('Something went wrong');

    $this->get('/definitely-missing-page')->assertNotFound()->assertSee('Page not found');
    $this->get('/preview/service/1?signature=bad')->assertForbidden();
});

it('validates the environment and exits non-zero for unsafe production settings', function () {
    $this->artisan('markedge:env-check')->assertSuccessful()->expectsOutputToContain('Environment check passed')->run();

    app()->detectEnvironment(fn () => 'production');
    config()->set('app.debug', true);
    config()->set('queue.default', 'sync');

    $this->artisan('markedge:env-check')->assertFailed()->expectsOutputToContain('FAIL')->doesntExpectOutputToContain(config('app.key'))->run();

    app()->detectEnvironment(fn () => 'testing');
});

it('keeps preview signed, noindex and non-cacheable', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
    $draft = Page::factory()->create(['slug' => 'secret-draft']);
    $this->actingAs(adminUser());

    $this->get(app(PreviewLink::class)->for($draft, auth()->id()))->assertOk()
        ->assertSee('noindex, nofollow', false)
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeaderMissing('Content-Security-Policy');

    auth()->logout();
    $this->get('/secret-draft')->assertNotFound();
});
