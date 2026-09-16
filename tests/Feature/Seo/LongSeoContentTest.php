<?php

use App\Filament\Resources\Services\Pages\EditService;
use App\Models\SeoMeta;
use App\Models\Service;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

it('saves and renders a 300-character title and a 600-character description through the admin', function () {
    $title = str_repeat('Markedge ', 33);
    $description = str_repeat('Detailed ', 66);
    $service = Service::factory()->published()->create();
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->actingAs(adminUser());

    Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
        ->fillForm(['seo' => ['title' => $title, 'description' => $description, 'og_title' => $title, 'twitter_description' => $description, 'robots_index' => true, 'robots_follow' => true, 'include_in_sitemap' => true]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(mb_strlen($service->fresh()->seo->title))->toBe(mb_strlen($title))->toBeGreaterThan(290);

    $this->get('/services/'.$service->slug)
        ->assertSee('<title>'.e($title).'</title>', false)
        ->assertSee('content="'.e($description).'"', false);
});

it('never strips SEO text in schema encoding even when it contains markup', function () {
    $service = Service::factory()->published()->create();
    SeoMeta::factory()->for($service, 'seoable')->create(['title' => 'Title with <b>bold</b> & "quotes"']);

    $content = $this->get('/services/'.$service->slug)->getContent();

    expect($content)->toContain('<title>Title with &lt;b&gt;bold&lt;/b&gt; &amp; &quot;quotes&quot;</title>')
        ->and($content)->not->toContain('<b>bold</b>');
});
