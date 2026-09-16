<?php

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Author;
use App\Models\CaseStudy;
use App\Models\Client;
use App\Models\Industry;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use App\Models\Tag;
use App\Models\Technology;

it('shows a service with its related records and hides unpublished ones', function () {
    $category = ServiceCategory::factory()->published()->create(['name' => 'Technology']);
    $service = Service::factory()->for($category, 'category')->published()->create(['name' => 'Software Development', 'benefits' => [['title' => 'Benefit one', 'text' => 'Because']], 'process' => [['title' => 'Discover'], ['title' => 'Build']]]);
    $sibling = Service::factory()->for($category, 'category')->published()->create(['name' => 'Sibling Service']);
    $product = Product::factory()->active()->create(['name' => 'Related Product']);
    $draftProduct = Product::factory()->create(['name' => 'Draft Product']);
    $industry = Industry::factory()->published()->create(['name' => 'Healthcare']);
    $draftIndustry = Industry::factory()->create(['name' => 'Draft Industry']);
    $solution = Solution::factory()->published()->create(['name' => 'Business Automation']);
    $article = Article::factory()->published()->create(['title' => 'Related Article']);
    $caseStudy = CaseStudy::factory()->published()->create(['title' => 'Related Case Study']);
    $technology = Technology::factory()->create(['name' => 'PHP']);

    $service->products()->attach([$product->id, $draftProduct->id]);
    $service->industries()->attach([$industry->id, $draftIndustry->id]);
    $service->solutions()->attach($solution);
    $service->caseStudies()->attach($caseStudy);
    $service->technologies()->attach($technology);
    $article->services()->attach($service);

    $this->get('/services/'.$service->slug)
        ->assertOk()
        ->assertSee('Benefit one')->assertSee('Discover')
        ->assertSee('Related Product')->assertDontSee('Draft Product')
        ->assertSee('Healthcare')->assertDontSee('Draft Industry')
        ->assertSee('Business Automation')
        ->assertSee('Related Article')
        ->assertSee('Related Case Study')
        ->assertSee('PHP')
        ->assertSee('Sibling Service')
        ->assertSee('Home')->assertSee('Technology');
});

it('renders the generic product template with features, modules and links', function () {
    $product = Product::factory()->active()->create(['name' => 'Recruitment Management System', 'tagline' => 'Recruitment, organised around performance.', 'integrations' => [['name' => 'Google Workspace', 'url' => 'https://workspace.google.com']]]);
    $product->features()->create(['title' => 'Pipeline view', 'group_label' => 'Sourcing']);
    $product->modules()->create(['name' => 'Interview scheduling', 'summary' => 'Coordinate interviews.']);
    $industry = Industry::factory()->published()->create(['name' => 'Recruitment']);
    $product->industries()->attach($industry);
    $service = Service::factory()->published()->create(['name' => 'Custom Software']);
    $product->services()->attach($service);

    $this->get('/products/'.$product->slug)
        ->assertOk()
        ->assertSee('Recruitment, organised around performance.')
        ->assertSee('Sourcing')->assertSee('Pipeline view')
        ->assertSee('Interview scheduling')
        ->assertSee('Google Workspace')
        ->assertSee('Recruitment')
        ->assertSee('Custom Software')
        ->assertDontSee('What users say');
});

it('renders solution and industry pages from their relationships', function () {
    $solution = Solution::factory()->published()->create(['name' => 'Sales Management', 'outcomes' => [['label' => 'Pipeline visibility', 'text' => 'Every lead tracked.']]]);
    $industry = Industry::factory()->published()->create(['name' => 'Real Estate', 'challenges' => [['title' => 'Lead leakage', 'text' => 'Enquiries get lost.']]]);
    $product = Product::factory()->active()->create(['name' => 'Lead Management System']);
    $solution->products()->attach($product);
    $solution->industries()->attach($industry);
    $industry->products()->attach($product);

    $this->get('/solutions/'.$solution->slug)->assertOk()->assertSee('Pipeline visibility')->assertSee('Lead Management System')->assertSee('Real Estate');
    $this->get('/industries/'.$industry->slug)->assertOk()->assertSee('Lead leakage')->assertSee('Lead Management System');
});

it('renders a case study with its outcomes and keeps hidden clients anonymous', function () {
    $client = Client::factory()->create(['name' => 'Secret Client', 'is_visible' => false]);
    $caseStudy = CaseStudy::factory()->published()->create(['client_id' => $client->id, 'title' => 'Automating reporting', 'outcomes' => [['label' => 'Reporting time', 'value' => 'Hours instead of days', 'kind' => 'qualitative']]]);

    $this->get('/case-studies/'.$caseStudy->slug)->assertOk()->assertSee('Hours instead of days')->assertDontSee('Secret Client');
});

it('renders an article with author, tags, related capabilities and keeps reading', function () {
    $author = Author::factory()->create(['name' => 'Asha Writer', 'bio' => '<p>Writes about cloud.</p>']);
    $category = ArticleCategory::factory()->create(['name' => 'Cloud']);
    $article = Article::factory()->published()->create(['author_id' => $author->id, 'article_category_id' => $category->id, 'title' => 'Moving to the cloud']);
    $article->tags()->attach(Tag::factory()->create(['name' => 'Migration']));
    $sibling = Article::factory()->published()->create(['article_category_id' => $category->id, 'title' => 'Sibling article']);
    $service = Service::factory()->published()->create(['name' => 'Cloud Solutions']);
    $article->services()->attach($service);

    $this->get('/insights/'.$article->slug)
        ->assertOk()
        ->assertSee('Asha Writer')->assertSee('Migration')->assertSee('Cloud Solutions')->assertSee('Sibling article')
        ->assertSee('min read')->assertSee('Insights')->assertSee('Cloud');
});
