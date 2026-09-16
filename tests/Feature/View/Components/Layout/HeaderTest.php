<?php

use App\Models\Cta;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Product;
use App\Models\Setting;

it('renders the header menu from the database', function () {
    $menu = Menu::factory()->create(['key' => 'header']);
    MenuItem::factory()->for($menu)->create(['label' => 'What We Do', 'url' => '/services']);
    MenuItem::factory()->for($menu)->hidden()->create(['label' => 'Hidden Item']);

    $this->get('/')
        ->assertOk()
        ->assertSee('What We Do')
        ->assertDontSee('Hidden Item');
});

it('lists visible products under the products menu without a menu edit', function () {
    $menu = Menu::factory()->create(['key' => 'header']);
    MenuItem::factory()->for($menu)->create(['label' => 'Products', 'url' => '/products', 'settings' => ['auto_children' => 'products']]);
    Product::factory()->active()->create(['name' => 'Recruitment Management System']);

    $this->get('/')->assertSee('Recruitment Management System');
});

it('renders the header CTA configured in settings', function () {
    Cta::factory()->create(['key' => 'talk-to-us', 'primary_label' => 'Talk to Us', 'primary_value' => '/contact']);
    Setting::factory()->create(['key' => 'cta.header', 'value' => 'talk-to-us']);

    $this->get('/')->assertSee('Talk to Us')->assertSee('href="/contact"', false);
});

it('renders without any menus or settings', function () {
    $this->get('/')->assertOk()->assertSee('Markedge');
});
