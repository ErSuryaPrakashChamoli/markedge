<?php

use App\Enums\PublishStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\CaseStudy;
use App\Models\Form;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;

it('renders every index page with an empty database', function (string $path, string $expected) {
    $this->get($path)->assertOk()->assertSee($expected);
})->with([
    'services' => ['/services', 'Services'],
    'products' => ['/products', 'Markedge Products'],
    'solutions' => ['/solutions', 'What are you trying to solve?'],
    'industries' => ['/industries', 'Industries'],
    'case studies' => ['/case-studies', 'Case studies will be published here'],
    'insights' => ['/insights', 'Insights'],
]);

it('renders a published record for each entity type and hides drafts', function (string $model, string $prefix) {
    $published = $model::factory()->published()->create();
    $draft = $model::factory()->create();

    $this->get($prefix.$published->slug)->assertOk()->assertSee(e($published->title ?? $published->name));
    $this->get($prefix.$draft->slug)->assertNotFound();
})->with([
    'service' => [Service::class, '/services/'],
    'service category' => [ServiceCategory::class, '/services/'],
    'solution' => [Solution::class, '/solutions/'],
    'industry' => [Industry::class, '/industries/'],
    'case study' => [CaseStudy::class, '/case-studies/'],
    'article' => [Article::class, '/insights/'],
    'page' => [Page::class, '/'],
    'landing page' => [LandingPage::class, '/lp/'],
]);

it('hides review, scheduled and archived content from public routes', function () {
    $review = Service::factory()->create(['status' => PublishStatus::Review]);
    $scheduled = Service::factory()->scheduled()->create();
    $archived = Service::factory()->archived()->create();

    $this->get('/services/'.$review->slug)->assertNotFound();
    $this->get('/services/'.$scheduled->slug)->assertNotFound();
    $this->get('/services/'.$archived->slug)->assertStatus(410);
});

it('shows active and coming-soon products but not drafts or archived ones', function () {
    $active = Product::factory()->active()->create();
    $soon = Product::factory()->comingSoon()->create();
    $draft = Product::factory()->create();
    $archived = Product::factory()->archived()->create();

    $this->get('/products/'.$active->slug)->assertOk()->assertSee($active->name);
    $this->get('/products/'.$soon->slug)->assertOk()->assertSee('Coming soon');
    $this->get('/products/'.$draft->slug)->assertNotFound();
    $this->get('/products/'.$archived->slug)->assertStatus(410);
    $this->get('/products')->assertSee($active->name)->assertSee($soon->name)->assertDontSee($draft->name);
});

it('never resolves public entities by numeric id', function () {
    $product = Product::factory()->active()->create();

    $this->get('/products/'.$product->id)->assertNotFound();
    $this->get('/services/'.Service::factory()->published()->create()->id)->assertNotFound();
});

it('keeps reserved sections out of the CMS page catch-all', function () {
    Page::factory()->published()->create(['slug' => 'about']);

    $this->get('/about')->assertOk();
    $this->get('/services/does-not-exist')->assertNotFound();
    $this->get('/Services')->assertNotFound();
    $this->get('/about/extra')->assertNotFound();
});

it('serves the contact page as a CMS page with its form', function () {
    $form = Form::factory()->create(['key' => 'general-enquiry', 'name' => 'General Enquiry']);
    Page::factory()->published()->create(['slug' => 'contact', 'title' => 'Contact', 'template' => 'contact', 'form_id' => $form->id, 'blocks' => []]);

    $this->get('/contact')->assertOk()->assertSee('Contact')->assertSee('wire:submit="submit"', false);
});

it('resolves a category and a service that share the /services namespace', function () {
    $category = ServiceCategory::factory()->published()->create(['slug' => 'technology', 'name' => 'Technology']);
    $service = Service::factory()->for($category, 'category')->published()->create(['slug' => 'web-development', 'name' => 'Web Development']);

    $this->get('/services/technology')->assertOk()->assertSee('Web Development');
    $this->get('/services/web-development')->assertOk()->assertSee('Web Development')->assertSee('Technology');
});

it('redirects an expired landing page instead of failing', function () {
    $landingPage = LandingPage::factory()->expired()->create(['expired_redirect_url' => '/products']);

    $this->get('/lp/'.$landingPage->slug)->assertRedirect('/products');
});

it('paginates and filters the insights archive', function () {
    $category = ArticleCategory::factory()->create(['slug' => 'cloud', 'name' => 'Cloud']);
    Article::factory()->published()->count(13)->create(['article_category_id' => $category->id]);
    Article::factory()->create(['title' => 'Unpublished article']);

    $this->get('/insights')->assertOk()->assertSee('page=2')->assertDontSee('Unpublished article');
    $this->get('/insights/category/cloud')->assertOk()->assertSee('Cloud');
    $this->get('/insights/category/missing')->assertNotFound();
});

it('renders the styleguide only with the flag', function () {
    config(['markedge.styleguide_enabled' => false]);

    $this->get('/styleguide')->assertNotFound();
});
