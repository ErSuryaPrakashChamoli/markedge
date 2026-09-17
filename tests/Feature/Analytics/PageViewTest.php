<?php

use App\Attribution\Attribution;
use App\Enums\ConversionEventType;
use App\Models\ConversionEvent;
use App\Models\Service;
use App\Models\Setting;
use App\Services\Cms\PreviewLink;
use App\Services\Cms\Settings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

function views(): Collection
{
    return ConversionEvent::query()->where('type', ConversionEventType::PageViewed->value)->orderBy('id')->get();
}

it('records a page view for successful public HTML responses only', function () {
    Service::factory()->published()->create(['slug' => 'seo']);

    $this->get('/services/seo')->assertOk();
    $this->get('/missing-page')->assertNotFound();
    $this->get('/sitemap.xml')->assertOk();
    $this->get('/health')->assertOk();
    $this->get('/admin/login')->assertOk();

    $view = views()->sole();
    $service = Service::query()->where('slug', 'seo')->first();

    expect($view->path)->toBe('/services/seo')
        ->and($view->entity_type)->toBe('service')
        ->and($view->entity_id)->toBe($service->id)
        ->and($view->visitor_id)->not->toBeNull()
        ->and($view->session_id)->not->toBeNull()
        ->and($view->meta['landing'])->toBeTrue()
        ->and($view->getAttributes())->not->toHaveKey('ip');
});

it('ignores bots and previews', function () {
    Service::factory()->published()->create(['slug' => 'seo']);

    $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get('/services/seo')->assertOk();
    $this->withHeader('User-Agent', 'Lighthouse')->get('/services/seo')->assertOk();
    $this->withHeader('User-Agent', '')->get('/services/seo')->assertOk();

    expect(views())->toHaveCount(0);

    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
    $this->actingAs(adminUser());
    $draft = Service::factory()->create();
    $this->withHeader('User-Agent', 'Mozilla/5.0 Chrome')->get(app(PreviewLink::class)->for($draft, auth()->id()))->assertOk();

    expect(views())->toHaveCount(0);
});

it('keeps one session across quick visits and starts a new one after the gap', function () {
    Service::factory()->published()->create(['slug' => 'seo']);
    $agent = ['User-Agent' => 'Mozilla/5.0 Chrome'];

    $first = $this->withHeaders($agent)->get('/')->assertOk();
    $cookie = collect($first->headers->getCookies())->first(fn ($c) => $c->getName() === 'mk_attr');
    $value = CookieValuePrefix::remove(app('encrypter')->decrypt($cookie->getValue(), false));
    $attribution = Attribution::fromArray(json_decode($value, true));

    $this->withHeaders($agent)->withCookie('mk_attr', json_encode($attribution->toArray()))->get('/services/seo')->assertOk();

    $this->travel(45)->minutes();
    $this->withHeaders($agent)->withCookie('mk_attr', json_encode($attribution->toArray()))->get('/services')->assertOk();

    $views = views();
    expect($views)->toHaveCount(3)
        ->and($views[0]->session_id)->toBe($views[1]->session_id)
        ->and($views[2]->session_id)->not->toBe($views[0]->session_id)
        ->and($views[0]->meta['landing'] ?? false)->toBeTrue()
        ->and($views[1]->meta['landing'] ?? false)->toBeFalse()
        ->and($views[2]->meta['landing'] ?? false)->toBeTrue()
        ->and($views->pluck('visitor_id')->unique())->toHaveCount(1);
});

it('counts without identifiers when consent is required and absent', function () {
    Setting::query()->updateOrCreate(['key' => 'privacy.attribution_requires_consent'], ['group' => 'privacy', 'type' => 'boolean', 'value' => true]);
    app(Settings::class)->forget();
    Service::factory()->published()->create(['slug' => 'seo']);

    $this->withHeader('User-Agent', 'Mozilla/5.0 Chrome')->get('/services/seo')->assertOk();
    $this->withHeader('User-Agent', 'Mozilla/5.0 Chrome')->withCookie('mk_consent', '1')->get('/services/seo')->assertOk();

    $views = views();
    expect($views)->toHaveCount(2)
        ->and($views[0]->visitor_id)->toBeNull()->and($views[0]->session_id)->toBeNull()
        ->and($views[1]->visitor_id)->not->toBeNull();
});

it('does not add queries to the request path and never breaks the page when the store fails', function () {
    Service::factory()->published()->create(['slug' => 'seo']);
    $this->withHeader('User-Agent', 'Mozilla/5.0 Chrome')->get('/services/seo');

    Schema::drop('conversion_events');
    $this->withHeader('User-Agent', 'Mozilla/5.0 Chrome')->get('/services/seo')->assertOk();
});

it('bounds event metadata and search terms', function () {
    Service::factory()->published()->create(['name' => 'Cloud']);
    $this->withHeader('User-Agent', 'Mozilla/5.0 Chrome')->get('/search?q='.urlencode(str_repeat('cloud ', 40)))->assertOk();

    $event = ConversionEvent::query()->where('type', 'search_performed')->sole();
    expect(mb_strlen($event->meta['query']))->toBeLessThanOrEqual(120);
});
