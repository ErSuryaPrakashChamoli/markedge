<?php

use App\Models\Cta;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;

it('renders a structural fallback when no home page is published', function () {
    $this->get('/')->assertOk()->assertSee('Technology that moves business forward.')->assertSee('Skip to content');
});

it('renders the published CMS home page from typed blocks', function () {
    $category = ServiceCategory::factory()->published()->create(['name' => 'Technology', 'pillar_label' => 'BUILD']);
    Service::factory()->for($category, 'category')->published()->create(['name' => 'Software Development']);
    Product::factory()->active()->create(['name' => 'Lead Management System']);
    $cta = Cta::factory()->create(['key' => 'start-conversation', 'primary_label' => 'Start a Conversation', 'primary_value' => '/contact']);

    Page::factory()->home()->published()->create(['blocks' => [
        ['type' => 'hero', 'data' => ['headline' => 'Technology that moves business forward.', 'primary_cta_id' => $cta->id, 'theme' => 'dark']],
        ['type' => 'capability_intro', 'data' => ['heading' => 'One technology partner. Multiple capabilities.']],
        ['type' => 'service_grid', 'data' => ['heading' => 'Build what your business needs.', 'mode' => 'category', 'service_category_id' => $category->id]],
        ['type' => 'product_showcase', 'data' => ['heading' => 'Markedge Products', 'mode' => 'all_active']],
        ['type' => 'case_study_grid', 'data' => ['heading' => 'Selected Work', 'mode' => 'latest']],
        ['type' => 'testimonials', 'data' => ['heading' => 'What clients say', 'mode' => 'all_visible']],
        ['type' => 'logo_cloud', 'data' => ['heading' => 'Trusted by', 'mode' => 'all_visible']],
        ['type' => 'article_grid', 'data' => ['heading' => 'Insights', 'mode' => 'latest']],
        ['type' => 'cta', 'data' => ['cta_id' => $cta->id]],
    ]]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Technology that moves business forward.')
        ->assertSee('Start a Conversation')
        ->assertSee('One technology partner. Multiple capabilities.')
        ->assertSee('Software Development')
        ->assertSee('Lead Management System')
        ->assertDontSee('Selected Work')
        ->assertDontSee('What clients say')
        ->assertDontSee('Trusted by')
        ->assertDontSee('>Insights<', false)
        ->assertSee('<h1', false);
});

it('keeps a single h1 on the CMS home page', function () {
    Page::factory()->home()->published()->create(['blocks' => [
        ['type' => 'hero', 'data' => ['headline' => 'Only one heading']],
        ['type' => 'rich_text', 'data' => ['body' => '<h2>Sub heading</h2><p>Body</p>']],
    ]]);

    expect(substr_count($this->get('/')->getContent(), '<h1'))->toBe(1);
});

it('does not expose the draft home page', function () {
    Page::factory()->home()->create(['blocks' => [['type' => 'hero', 'data' => ['headline' => 'Draft only headline']]]]);
    Setting::factory()->create(['key' => 'company.name', 'value' => 'Markedge Technologies']);

    $this->get('/')->assertOk()->assertDontSee('Draft only headline');
});
