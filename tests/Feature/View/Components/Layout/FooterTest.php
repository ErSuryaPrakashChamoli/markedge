<?php

use App\Enums\PageTemplate;
use App\Models\Form;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;
use App\Models\SocialLink;
use Illuminate\Support\Str;

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

it('links a footer column title to its page when the item has a URL', function () {
    $footer = Menu::factory()->create(['key' => 'footer']);
    $column = MenuItem::factory()->for($footer)->create(['label' => 'Technology', 'url' => '/services/technology']);
    MenuItem::factory()->for($footer)->create(['label' => 'Cloud', 'url' => '/services/cloud', 'parent_id' => $column->id]);

    $this->get('/')
        ->assertSee('href="/services/technology"', false)
        ->assertSee('Technology')
        ->assertSee('href="/services/cloud"', false);
});

it('renders a plain heading column without a link', function () {
    $footer = Menu::factory()->create(['key' => 'footer']);
    $column = MenuItem::factory()->for($footer)->heading()->create(['label' => 'Company']);
    MenuItem::factory()->for($footer)->create(['label' => 'About', 'url' => '/about', 'parent_id' => $column->id]);

    $this->get('/')
        ->assertSee('Company')
        ->assertDontSee('>Company</a>', false);
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

it('renders the contact section with settings details and a contact button', function () {
    Setting::factory()->create(['key' => 'contact.phone', 'value' => '+91 98765 43210']);
    Setting::factory()->create(['key' => 'contact.address', 'value' => 'Dehradun, India']);

    $this->get('/')
        ->assertSee('Get in touch')
        ->assertSee('+91 98765 43210')
        ->assertSee('Dehradun, India')
        ->assertSee('href="'.url('/contact').'"', false)
        ->assertSee('Contact us');
});

it('places the contact block under the tagline with the address after the email and phone', function () {
    Setting::factory()->create(['key' => 'company.tagline', 'value' => 'Build. Operate. Grow.']);
    Setting::factory()->create(['key' => 'contact.email', 'value' => 'hello@example.test']);
    Setting::factory()->create(['key' => 'contact.phone', 'value' => '+91 98765 43210']);
    Setting::factory()->create(['key' => 'contact.address', 'value' => 'Sector 2, Noida']);
    $footer = Menu::factory()->create(['key' => 'footer']);
    $column = MenuItem::factory()->for($footer)->heading()->create(['label' => 'Company']);
    MenuItem::factory()->for($footer)->create(['label' => 'Careers', 'url' => '/careers', 'parent_id' => $column->id]);

    $this->get('/')->assertSeeInOrder(['Build. Operate. Grow.', 'Get in touch', 'hello@example.test', '+91 98765 43210', 'Sector 2, Noida', 'Contact us', 'Careers']);
});

it('leaves the company description out of the footer', function () {
    Setting::factory()->create(['key' => 'company.description', 'value' => 'A-70, Third Floor, Noida']);

    $footer = Str::after($this->get('/')->assertOk()->getContent(), '<footer');

    expect($footer)->not->toContain('A-70, Third Floor, Noida');
});

it('renders social links as brand icons with accessible labels', function () {
    SocialLink::factory()->create(['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://linkedin.com/company/markedge']);

    $this->get('/')
        ->assertSee('aria-label="LinkedIn"', false)
        ->assertSee('href="https://linkedin.com/company/markedge"', false)
        ->assertSee('<svg', false);
});

it('uses the copyright line from settings with the current year filled in', function () {
    Setting::factory()->create(['key' => 'footer.copyright', 'value' => '© {year} Markedge Technologies Pvt. Ltd.']);
    $this->freezeTime();

    $this->get('/')
        ->assertSee('© '.now()->year.' Markedge Technologies Pvt. Ltd.')
        ->assertDontSee('All rights reserved.');
});

it('places the social icons in the bottom bar after the copyright', function () {
    SocialLink::factory()->create(['platform' => 'instagram', 'label' => 'Instagram']);

    $this->get('/')->assertSeeInOrder(['All rights reserved.', 'aria-label="Instagram"'], false);
});

it('shows the office map under the footer link columns', function () {
    $embed = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3503';
    Setting::factory()->create(['key' => 'contact.map_embed', 'value' => $embed]);

    $this->get('/')->assertOk()->assertSee('src="'.e($embed).'"', false);
});

it('leaves the footer map out when no office location is set', function () {
    $this->get('/')->assertOk()->assertDontSee('<iframe', false);
});

it('shows a single map on a page that already has the office map', function () {
    Setting::factory()->create(['key' => 'contact.map_location', 'value' => 'Noida']);
    $page = Page::factory()->published()->create([
        'template' => PageTemplate::Contact,
        'blocks' => [['type' => 'contact_form', 'data' => ['form_id' => Form::factory()->create()->id]]],
    ]);

    expect(substr_count($this->get('/'.$page->slug)->assertOk()->getContent(), '<iframe'))->toBe(1);
});
