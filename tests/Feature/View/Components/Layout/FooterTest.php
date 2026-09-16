<?php

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\SocialLink;

it('renders footer columns and legal links from their menus', function () {
    $footer = Menu::factory()->create(['key' => 'footer']);
    $column = MenuItem::factory()->for($footer)->heading()->create(['label' => 'Company']);
    MenuItem::factory()->for($footer)->create(['label' => 'Careers', 'url' => '/careers', 'parent_id' => $column->id]);
    $legal = Menu::factory()->create(['key' => 'legal']);
    MenuItem::factory()->for($legal)->create(['label' => 'Privacy Policy', 'url' => '/privacy-policy']);

    $this->get('/')
        ->assertSee('Company')
        ->assertSee('Careers')
        ->assertSee('Privacy Policy');
});

it('shows only visible social links', function () {
    SocialLink::factory()->create(['platform' => 'linkedin', 'label' => 'LinkedIn']);
    SocialLink::factory()->hidden()->create(['platform' => 'facebook', 'label' => 'Facebook']);

    $this->get('/')->assertSee('LinkedIn')->assertDontSee('Facebook');
});

it('shows contact details from settings', function () {
    Setting::factory()->create(['key' => 'contact.email', 'value' => 'hello@example.test']);

    $this->get('/')->assertSee('mailto:hello@example.test');
});

it('shows the copyright with the company name and year', function () {
    Setting::factory()->create(['key' => 'company.name', 'value' => 'Markedge Technologies']);
    $this->freezeTime();

    $this->get('/')->assertSee('&copy; '.now()->year.' Markedge Technologies', false);
});
