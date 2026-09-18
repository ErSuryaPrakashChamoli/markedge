<?php

use App\Cms\Blocks\BlockRenderer;
use App\Models\Article;
use App\Models\Client;
use App\Models\Cta;
use App\Models\Faq;
use App\Models\Form;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use App\Models\Technology;
use App\Models\Testimonial;

function pageWithBlocks(array $blocks): Page
{
    return Page::factory()->published()->create(['blocks' => $blocks]);
}

it('renders every content block type through its Blade view', function () {
    $cta = Cta::factory()->create();
    $form = Form::factory()->create();
    $page = pageWithBlocks([
        ['type' => 'hero', 'data' => ['headline' => 'Hero headline', 'eyebrow' => 'Eyebrow']],
        ['type' => 'split_content', 'data' => ['heading' => 'Split heading', 'body' => '<p>Split body</p>', 'bullets' => ['Point one'], 'cta_id' => $cta->id]],
        ['type' => 'rich_text', 'data' => ['body' => '<p>Rich body</p>']],
        ['type' => 'image_content', 'data' => ['image' => 'blocks/test.jpg', 'image_alt' => 'An image', 'caption' => 'Image caption']],
        ['type' => 'video', 'data' => ['provider' => 'youtube', 'source' => 'https://www.youtube.com/watch?v=abcdef12345', 'caption' => 'Video caption']],
        ['type' => 'feature_grid', 'data' => ['heading' => 'Feature heading', 'items' => [['title' => 'Feature one', 'text' => 'Feature text']]]],
        ['type' => 'stats', 'data' => ['items' => [['value' => '24', 'label' => 'Services']], 'attested' => true]],
        ['type' => 'process', 'data' => ['heading' => 'Process heading', 'steps' => [['title' => 'Discover'], ['title' => 'Define']]]],
        ['type' => 'timeline', 'data' => ['entries' => [['date_label' => '2026', 'title' => 'Timeline entry']]]],
        ['type' => 'comparison', 'data' => ['columns' => ['Basic', 'Pro'], 'rows' => [['label' => 'Row label', 'values' => ['yes', 'no']]]]],
        ['type' => 'cta', 'data' => ['cta_id' => $cta->id, 'heading' => 'CTA heading']],
        ['type' => 'lead_form', 'data' => ['form_id' => $form->id, 'heading' => 'Lead form heading']],
    ]);

    $this->get('/'.$page->slug)
        ->assertOk()
        ->assertSeeInOrder(['Hero headline', 'Split heading', 'Point one', 'Rich body', 'Image caption', 'Video caption', 'Feature one', 'Services', 'Discover', 'Timeline entry', 'Row label', 'CTA heading', 'Lead form heading'])
        ->assertSee('youtube-nocookie.com/embed/abcdef12345')
        ->assertSee($cta->primary_label);
});

it('resolves relationship blocks from published records only', function () {
    $category = ServiceCategory::factory()->published()->create(['name' => 'Digital Growth']);
    Service::factory()->for($category, 'category')->published()->create(['name' => 'Published Service']);
    Service::factory()->for($category, 'category')->create(['name' => 'Draft Service']);
    Product::factory()->active()->create(['name' => 'Visible Product']);
    Product::factory()->create(['name' => 'Draft Product']);
    Solution::factory()->published()->featured()->create(['name' => 'Featured Solution']);
    Solution::factory()->featured()->create(['name' => 'Draft Solution']);
    Technology::factory()->create(['name' => 'Laravel']);
    Technology::factory()->hidden()->create(['name' => 'HiddenTech']);
    Client::factory()->inLogoCloud()->create(['name' => 'Visible Client']);
    Client::factory()->create(['name' => 'Hidden Client']);
    Testimonial::factory()->visible()->create(['author_name' => 'Visible Person']);
    Testimonial::factory()->create(['author_name' => 'Hidden Person']);
    Article::factory()->published()->create(['title' => 'Published Article']);
    Article::factory()->create(['title' => 'Draft Article']);

    $page = pageWithBlocks([
        ['type' => 'service_grid', 'data' => ['mode' => 'category', 'service_category_id' => $category->id]],
        ['type' => 'product_showcase', 'data' => ['mode' => 'all_active']],
        ['type' => 'solution_grid', 'data' => ['mode' => 'featured']],
        ['type' => 'technology_grid', 'data' => ['mode' => 'all']],
        ['type' => 'logo_cloud', 'data' => ['mode' => 'all_visible']],
        ['type' => 'testimonials', 'data' => ['mode' => 'all_visible']],
        ['type' => 'article_grid', 'data' => ['mode' => 'latest']],
    ]);

    $this->get('/'.$page->slug)
        ->assertOk()
        ->assertSee('Published Service')->assertDontSee('Draft Service')
        ->assertSee('Visible Product')->assertDontSee('Draft Product')
        ->assertSee('Featured Solution')->assertDontSee('Draft Solution')
        ->assertSee('Laravel')->assertDontSee('HiddenTech')
        ->assertSee('Visible Client')->assertDontSee('Hidden Client')
        ->assertSee('Visible Person')->assertDontSee('Hidden Person')
        ->assertSee('Published Article')->assertDontSee('Draft Article');
});

it('drops sections whose records are empty or unpublished', function () {
    $page = pageWithBlocks([
        ['type' => 'service_grid', 'data' => ['heading' => 'Empty services', 'mode' => 'ids', 'service_ids' => [Service::factory()->create()->id]]],
        ['type' => 'case_study_grid', 'data' => ['heading' => 'Empty case studies', 'mode' => 'latest']],
        ['type' => 'faq', 'data' => ['heading' => 'Empty faqs', 'mode' => 'host_faqs']],
        ['type' => 'rich_text', 'data' => ['body' => '<p></p>']],
        ['type' => 'cta', 'data' => ['cta_id' => 999999]],
    ]);

    $prepared = app(BlockRenderer::class)->prepare($page->enabledBlocks(), $page);

    expect($prepared)->toBe([]);
    $this->get('/'.$page->slug)->assertOk()->assertDontSee('Empty services')->assertDontSee('Empty case studies')->assertDontSee('Empty faqs');
});

it('skips disabled blocks and unknown block types publicly', function () {
    $page = pageWithBlocks([
        ['type' => 'rich_text', 'data' => ['body' => '<p>Disabled body</p>', 'is_enabled' => false]],
        ['type' => 'raw_html', 'data' => ['html' => '<script>alert(1)</script>']],
        ['type' => 'rich_text', 'data' => ['body' => '<p>Visible body</p>']],
        'garbage',
    ]);

    $response = $this->get('/'.$page->slug)->assertOk()->assertSee('Visible body')->assertDontSee('Disabled body');

    expect($response->getContent())->not->toContain('alert(1)')->not->toContain('raw_html');
});

it('keeps admin-chosen order for explicit record selections', function () {
    $second = Service::factory()->published()->create(['name' => 'Second Pick']);
    $first = Service::factory()->published()->create(['name' => 'First Pick']);
    $page = pageWithBlocks([['type' => 'service_grid', 'data' => ['mode' => 'ids', 'service_ids' => [$first->id, $second->id]]]]);

    $this->get('/'.$page->slug)->assertSeeInOrder(['First Pick', 'Second Pick']);
});

it('renders host FAQs through the faq block and hidden ones stay out', function () {
    $page = pageWithBlocks([['type' => 'faq', 'data' => ['mode' => 'host_faqs']]]);
    Faq::factory()->for($page, 'faqable')->create(['question' => 'Visible question?']);
    Faq::factory()->for($page, 'faqable')->create(['question' => 'Hidden question?', 'is_visible' => false]);

    $this->get('/'.$page->slug)->assertSee('Visible question?')->assertDontSee('Hidden question?');
});

it('escapes user-entered text in blocks and titles', function () {
    $page = pageWithBlocks([['type' => 'hero', 'data' => ['headline' => '<script>alert("x")</script> Safe']]]);

    $response = $this->get('/'.$page->slug)->assertOk();

    expect($response->getContent())->toContain('&lt;script&gt;')->not->toContain('<script>alert("x")');
});

it('alternates light and neutral backgrounds for consecutive default-theme blocks', function () {
    $prepared = app(BlockRenderer::class)->prepare([
        ['type' => 'rich_text', 'data' => ['body' => '<p>One</p>']],
        ['type' => 'rich_text', 'data' => ['body' => '<p>Two</p>', 'theme' => 'light']],
        ['type' => 'rich_text', 'data' => ['body' => '<p>Three</p>', 'theme' => 'dark']],
        ['type' => 'rich_text', 'data' => ['body' => '<p>Four</p>']],
        ['type' => 'rich_text', 'data' => ['body' => '<p>Five</p>', 'theme' => 'neutral']],
    ]);

    expect(array_column(array_column($prepared, 'data'), 'theme'))->toBe(['light', 'neutral', 'dark', 'light', 'neutral']);
});
