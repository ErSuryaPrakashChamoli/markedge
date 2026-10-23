<?php

use App\Enums\PageTemplate;
use App\Models\Form;
use App\Models\Page;
use App\Models\Setting;
use App\Services\Cms\GoogleMap;

function contactPage(array $blockData = []): Page
{
    return Page::factory()->published()->create([
        'template' => PageTemplate::Contact,
        'blocks' => [['type' => 'contact_form', 'data' => ['form_id' => Form::factory()->create()->id, 'heading' => 'Contact Markedge'] + $blockData]],
    ]);
}

it('pins the office address on the contact map when no map location is set', function () {
    Setting::factory()->create(['key' => 'contact.address', 'value' => "12 Rajpur Road,\nDehradun"]);
    Setting::factory()->create(['key' => 'contact.country', 'value' => 'India']);

    $response = $this->get('/'.contactPage()->slug);

    $response->assertOk()
        ->assertSee('src="'.e(GoogleMap::embedUrlFor('12 Rajpur Road, Dehradun, India')).'"', false)
        ->assertSee('href="'.e(GoogleMap::directionsUrlFor('12 Rajpur Road, Dehradun, India')).'"', false);
    expect($response->headers->get('Content-Security-Policy'))->toContain('https://www.google.com');
});

it('prefers the map location over the address', function () {
    Setting::factory()->create(['key' => 'contact.address', 'value' => '12 Rajpur Road']);
    Setting::factory()->create(['key' => 'contact.map_location', 'value' => '30.3165, 78.0322']);

    $this->get('/'.contactPage()->slug)
        ->assertSee('src="'.e(GoogleMap::embedUrlFor('30.3165, 78.0322')).'"', false)
        ->assertDontSee(e(GoogleMap::embedUrlFor('12 Rajpur Road')), false);
});

it('uses pasted Google Maps embed code for an exact pin', function () {
    $embed = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3443';
    Setting::factory()->create(['key' => 'contact.map_embed', 'value' => $embed]);
    Setting::factory()->create(['key' => 'contact.map_location', 'value' => 'Dehradun']);

    $this->get('/'.contactPage()->slug)->assertSee('src="'.e($embed).'"', false);
});

it('hides the map when no location is known, and in the contact section when the block turns it off', function () {
    $this->get('/'.contactPage()->slug)->assertOk()->assertDontSee('<iframe', false);

    Setting::factory()->create(['key' => 'contact.map_location', 'value' => 'Dehradun']);

    // The footer always carries the map, so the contact section adds the second one.
    expect(substr_count($this->get('/'.contactPage()->slug)->getContent(), '<iframe'))->toBe(2)
        ->and(substr_count($this->get('/'.contactPage(['show_map' => false])->slug)->getContent(), '<iframe'))->toBe(1);
});

it('reduces embed code to its Google Maps source', function (string $input, ?string $expected) {
    expect(GoogleMap::embedSource($input))->toBe($expected);
})->with([
    'iframe code' => ['<iframe src="https://www.google.com/maps/embed?pb=!1m18&amp;x=1" width="600" style="border:0;" allowfullscreen=""></iframe>', 'https://www.google.com/maps/embed?pb=!1m18&x=1'],
    'bare embed URL' => ['https://www.google.com/maps/embed?pb=!1m18', 'https://www.google.com/maps/embed?pb=!1m18'],
    'share link' => ['https://maps.app.goo.gl/abc123', null],
    'foreign iframe' => ['<iframe src="https://evil.example/maps/embed?pb=1"></iframe>', null],
]);
