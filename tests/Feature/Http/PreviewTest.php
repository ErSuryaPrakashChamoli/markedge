<?php

use App\Models\Page;
use App\Models\Service;
use App\Models\Technology;
use App\Services\Cms\PreviewLink;
use Illuminate\Support\Facades\URL;

it('renders a draft through a signed preview link with noindex headers', function () {
    $page = Page::factory()->create(['title' => 'Unpublished page', 'blocks' => [['type' => 'rich_text', 'data' => ['body' => '<p>Draft body</p>']]]]);

    $this->get(app(PreviewLink::class)->for($page, userId: 1))
        ->assertOk()
        ->assertSee('Unpublished page')
        ->assertSee('Preview')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive">', false);
});

it('rejects unsigned preview requests', function () {
    $page = Page::factory()->create();

    $this->get("/preview/page/{$page->id}")->assertForbidden();
});

it('rejects expired preview links', function () {
    $page = Page::factory()->create();
    $url = app(PreviewLink::class)->for($page, userId: 1);

    $this->travel(config('markedge.preview.ttl_hours') + 1)->hours();

    $this->get($url)->assertForbidden();
});

it('returns 404 for unknown preview types and records', function () {
    $service = Service::factory()->create();

    $this->get(app(PreviewLink::class)->for($service, userId: 1))->assertOk();
    $this->get(URL::temporarySignedRoute('preview.show', now()->addHour(), ['type' => 'service', 'id' => 999999]))->assertNotFound();
    $this->get(URL::temporarySignedRoute('preview.show', now()->addHour(), ['type' => 'technology', 'id' => 1]))->assertNotFound();
});

it('only supports the previewable entity types', function () {
    $links = app(PreviewLink::class);

    expect($links->supports(Page::factory()->create()))->toBeTrue()
        ->and($links->supports(Technology::factory()->create()))->toBeFalse();
});
