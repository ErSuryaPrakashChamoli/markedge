<?php

use App\Models\Setting;

it('allows public crawling, blocks private routes and references the sitemap when indexable', function () {
    Setting::factory()->create(['key' => 'seo.canonical_host', 'value' => 'https://www.markedge.example']);

    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('User-agent: *')
        ->assertSee('Disallow: /admin')
        ->assertSee('Disallow: /preview')
        ->assertSee('Allow: /')
        ->assertSee('Sitemap: https://www.markedge.example/sitemap.xml')
        ->assertDontSee('Disallow: /'."\n", false);
});

it('blocks everything when the environment is not indexable', function () {
    config(['markedge.seo.indexable' => false]);

    $content = $this->get('/robots.txt')->assertOk()->getContent();

    expect($content)->toBe("User-agent: *\nDisallow: /\n");
});

it('never includes CMS content in robots directives', function () {
    Setting::factory()->create(['key' => 'company.name', 'value' => "Evil\nAllow: /admin"]);

    $content = $this->get('/robots.txt')->getContent();

    expect($content)->not->toContain('Evil')->and(substr_count($content, 'Allow: /admin'))->toBe(0);
});
