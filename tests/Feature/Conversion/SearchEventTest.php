<?php

use App\Attribution\Attribution;
use App\Models\ConversionEvent;
use App\Models\Service;
use Illuminate\Support\Facades\Schema;

it('records an aggregate search event with the anonymous visitor id and no personal data', function () {
    Service::factory()->published()->create(['name' => 'Cloud']);
    $attribution = Attribution::fresh();

    $this->withCookie('mk_attr', json_encode($attribution->toArray()))->get('/search?q=Cloud%20Services')->assertOk();
    $this->get('/search?q=a')->assertOk();
    $this->get('/search')->assertOk();

    $event = ConversionEvent::query()->where('type', 'search_performed')->sole();

    expect($event->type->value)->toBe('search_performed')
        ->and($event->visitor_id)->toBe($attribution->visitorId)
        ->and($event->meta)->toBe(['query' => 'cloud services', 'results' => 1, 'type' => null])
        ->and($event->getAttributes())->not->toHaveKey('ip')
        ->and($event->getAttributes())->not->toHaveKey('user_agent');
});

it('keeps search working when the event store fails', function () {
    Service::factory()->published()->create(['name' => 'Cloud']);
    Schema::drop('conversion_events');

    $this->get('/search?q=cloud')->assertOk()->assertSee('Cloud');
});
