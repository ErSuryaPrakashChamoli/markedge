<?php

use App\Models\Product;
use App\Models\Service;

it('gives each page type its own scroll-reveal signature and never hides content without JavaScript', function () {
    $service = Service::factory()->published()->create();
    Product::factory()->active()->create(['slug' => 'lms']);

    $this->get('/')->assertOk()->assertSee('data-motion="rise"', false)->assertSee("classList.add('js-motion')", false)->assertSee("history.scrollRestoration = 'manual'", false);
    $this->get('/services/'.$service->slug)->assertOk()->assertSee('data-motion="slide"', false);
    $this->get('/products/lms')->assertOk()->assertSee('data-motion="scale"', false);
    $this->get('/products')->assertOk()->assertSee('data-motion="scale"', false);
    $this->get('/insights')->assertOk()->assertSee('data-motion="lift"', false);
});
