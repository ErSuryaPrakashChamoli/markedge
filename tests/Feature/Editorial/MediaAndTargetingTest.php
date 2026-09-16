<?php

use App\Attribution\Attribution;
use App\Attribution\Touch;
use App\Editorial\MediaUsage;
use App\Enums\CampaignStatus;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Support\MediaFields;
use App\Models\Article;
use App\Models\Campaign;
use App\Models\Cta;
use App\Models\Service;
use App\Models\Setting;
use App\Services\Cms\CampaignTargeting;
use App\Services\Cms\PreviewLink;
use App\Services\Cms\Settings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
});

it('detects unused media and keeps deletion an explicit action', function () {
    $this->actingAs(adminUser());
    $article = Article::factory()->create();
    $article->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection('featured');
    $gone = Article::factory()->create();
    $gone->addMedia(UploadedFile::fake()->image('b.jpg'))->toMediaCollection('featured');
    DB::table('articles')->where('id', $gone->id)->delete();

    $orphans = MediaUsage::orphaned(Media::query())->get();

    expect(Media::count())->toBe(2)->and($orphans)->toHaveCount(1)->and($orphans->first()->file_name)->toBe('b.jpg')
        ->and(MediaUsage::label(Media::query()->where('file_name', 'a.jpg')->first()))->toContain('not public')
        ->and(MediaUsage::label($orphans->first()))->toContain('missing, unused');

    Livewire::test(ListMedia::class)->filterTable('usage', 'orphaned')->assertCanSeeTableRecords($orphans)->assertCanNotSeeTableRecords(Media::query()->where('file_name', 'a.jpg')->get());
});

it('keeps SVG uploads blocked and media edits permission gated', function () {
    $this->actingAs(adminUser('Content Manager'));
    $article = Article::factory()->create();

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['featured' => [UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')]])
        ->call('save')->assertHasFormErrors(['featured']);

    expect(MediaFields::IMAGE_TYPES)->not->toContain('image/svg+xml');

    $this->actingAs(adminUser('Sales'));
    $this->get(MediaResource::getUrl('index'))->assertForbidden();
});

it('swaps the CTA for running campaign visitors only, with a deterministic default and stable SEO', function () {
    $default = Cta::factory()->create(['key' => 'start-conversation', 'primary_label' => 'Default CTA']);
    $campaignCta = Cta::factory()->create(['key' => 'campaign-cta', 'primary_label' => 'Campaign CTA', 'primary_value' => '/request-quote']);
    Setting::query()->updateOrCreate(['key' => 'cta.default_service'], ['group' => 'cta', 'type' => 'text', 'value' => 'start-conversation']);
    app(Settings::class)->forget();
    $campaign = Campaign::factory()->create(['utm_campaign' => 'q4', 'status' => CampaignStatus::Active, 'cta_id' => $campaignCta->id, 'personalize_cta' => true]);
    $service = Service::factory()->published()->create(['slug' => 'seo']);

    $plain = $this->get('/services/seo')->assertOk()->assertSee('Default CTA')->assertDontSee('Campaign CTA');
    $targeted = $this->get('/services/seo?utm_campaign=q4')->assertOk()->assertSee('Campaign CTA');

    $canonical = fn ($response) => preg_match('/<link rel="canonical" href="([^"]+)"/', $response->getContent(), $m) ? $m[1] : null;
    $schema = fn ($response) => preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $m) ? $m[1] : null;
    expect($canonical($targeted))->toBe($canonical($plain))->toBe('http://localhost/services/seo')
        ->and($schema($targeted))->toBe($schema($plain))
        ->and($targeted->getContent())->toContain('<meta name="robots" content="index, follow">');

    $campaign->update(['personalize_cta' => false]);
    $this->get('/services/seo?utm_campaign=q4')->assertSee('Default CTA')->assertDontSee('Campaign CTA');

    $campaign->update(['personalize_cta' => true, 'status' => CampaignStatus::Ended]);
    $this->get('/services/seo?utm_campaign=q4')->assertSee('Default CTA');

    $campaign->update(['status' => CampaignStatus::Active]);
    $campaignCta->update(['is_active' => false]);
    $this->get('/services/seo?utm_campaign=q4')->assertSee('Default CTA');

    $this->get('/sitemap.xml')->assertDontSee('utm_campaign');
});

it('never targets on previews or personal data', function () {
    $campaignCta = Cta::factory()->create(['key' => 'campaign-cta', 'primary_label' => 'Campaign CTA', 'primary_value' => '/request-quote']);
    Campaign::factory()->create(['utm_campaign' => 'q4', 'status' => CampaignStatus::Active, 'cta_id' => $campaignCta->id, 'personalize_cta' => true]);
    $service = Service::factory()->published()->create(['slug' => 'seo']);
    $this->actingAs(adminUser());

    $attribution = Attribution::fresh();
    $attribution->first = $attribution->last = new Touch('linkedin', 'social', 'q4', null, null, null, '/', now()->toIso8601String());
    $this->withCookie('mk_attr', json_encode($attribution->toArray()))->get(app(PreviewLink::class)->for($service, auth()->id()))->assertOk()->assertDontSee('Campaign CTA');
    $this->withCookie('mk_attr', json_encode($attribution->toArray()))->get('/services/seo')->assertOk()->assertSee('Campaign CTA');

    expect(CampaignTargeting::explain(Campaign::query()->first()))->toContain('utm_campaign=q4')
        ->and((new ReflectionClass(CampaignTargeting::class))->getFileName())->not->toContain('lead');
});
