<?php

use App\Models\Cta;
use App\Models\Form;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use App\Services\Cms\PreviewLink;

it('renders drafts through the real entity templates', function () {
    $service = Service::factory()->create(['name' => 'Draft Service Name', 'benefits' => [['title' => 'Draft benefit']]]);
    $product = Product::factory()->create(['name' => 'Draft Product Name']);
    $page = Page::factory()->create(['title' => 'Draft Page', 'blocks' => [['type' => 'rich_text', 'data' => ['body' => '<p>Draft rich text</p>']]]]);

    $this->get(app(PreviewLink::class)->for($service, 1))->assertOk()->assertSee('Draft Service Name')->assertSee('Draft benefit');
    $this->get(app(PreviewLink::class)->for($product, 1))->assertOk()->assertSee('Draft Product Name');
    $this->get(app(PreviewLink::class)->for($page, 1))->assertOk()->assertSee('Draft rich text');
});

it('keeps previews out of search engines and strips canonical and tracking', function () {
    Setting::factory()->create(['key' => 'tracking.gtm_container_id', 'value' => 'GTM-TEST123']);
    $page = Page::factory()->create(['title' => 'Preview me']);

    $this->get(app(PreviewLink::class)->for($page, 1))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive">', false)
        ->assertDontSee('rel="canonical"', false)
        ->assertDontSee('GTM-TEST123')
        ->assertSee('Preview')
        ->assertSee('<title>Preview: Preview me', false);
});

it('labels unknown blocks in preview instead of hiding them', function () {
    $page = Page::factory()->create(['blocks' => [['type' => 'raw_html', 'data' => []]]]);

    $this->get(app(PreviewLink::class)->for($page, 1))->assertOk()->assertSee('not registered');
});

it('disables form submission in landing page previews', function () {
    $landingPage = LandingPage::factory()->create(['form_id' => Form::factory()->create()->id, 'cta_id' => Cta::factory()->create()->id]);

    $this->get(app(PreviewLink::class)->for($landingPage, 1))->assertOk()->assertSee('Submissions are disabled in preview.');
});
