<?php

use App\Enums\ConversionEventType;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Products\RelationManagers\FeaturesRelationManager;
use App\Jobs\SyncSearchEntry;
use App\Models\ConversionEvent;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ProductCapability;
use App\Models\ProductDocument;
use App\Models\ProductFeature;
use App\Models\ProductModule;
use App\Models\SearchEntry;
use App\Seo\Sitemap\SitemapGenerator;
use App\Services\Cms\PreviewLink;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

function platformProduct(string $name = 'LMS'): Product
{
    return Product::factory()->active()->create(['name' => $name, 'slug' => strtolower($name)]);
}

it('renders the product hierarchy module → feature → capability and factual deployment and security sections', function () {
    $product = platformProduct();
    $module = ProductModule::factory()->create(['product_id' => $product->id, 'name' => 'Capture module']);
    $feature = ProductFeature::factory()->create(['product_id' => $product->id, 'product_module_id' => $module->id, 'title' => 'Web forms']);
    ProductCapability::factory()->create(['product_feature_id' => $feature->id, 'name' => 'Spam filtering']);
    ProductFeature::factory()->create(['product_id' => $product->id, 'title' => 'Standalone feature', 'group_label' => 'Reporting']);

    $this->get('/products/lms')->assertOk()
        ->assertSee('Capture module')->assertSee('Web forms')->assertSee('Spam filtering')->assertSee('Standalone feature')->assertSee('Reporting')
        ->assertDontSee('Deployment options')->assertDontSee('Security');

    $product->update(['deployment' => [['label' => 'Cloud hosted', 'text' => 'Hosted and operated by Markedge.']], 'security' => [['label' => 'Role-based access', 'text' => 'Permissions per user role.']]]);

    $this->get('/products/lms')->assertOk()->assertSee('Deployment options')->assertSee('Cloud hosted')->assertSee('Role-based access');
});

it('compares publicly visible products from stored modules and features only', function () {
    $lms = platformProduct('LMS');
    ProductModule::factory()->create(['product_id' => $lms->id, 'name' => 'Pipeline']);
    ProductFeature::factory()->create(['product_id' => $lms->id, 'title' => 'Attribution']);

    $this->get('/products/compare')->assertNotFound();

    $rms = platformProduct('RMS');
    ProductModule::factory()->create(['product_id' => $rms->id, 'name' => 'pipeline']);
    ProductFeature::factory()->create(['product_id' => $rms->id, 'title' => 'Interview scheduling']);
    Product::factory()->create(['name' => 'Draft product', 'slug' => 'draft-product']);

    $response = $this->get('/products/compare')->assertOk()
        ->assertSee('LMS')->assertSee('RMS')->assertDontSee('Draft product')
        ->assertSee('Attribution')->assertSee('Interview scheduling')->assertSee('Not listed');

    expect(substr_count($response->getContent(), 'Pipeline'))->toBe(1)
        ->and(collect(app(SitemapGenerator::class)->entries())->pluck('loc'))->toContain(url('/products/compare'));
});

it('publishes product documentation with canonical, breadcrumbs, schema, sitemap, search and preview', function () {
    $product = platformProduct();
    $draft = ProductDocument::factory()->create(['product_id' => $product->id, 'title' => 'Draft guide', 'slug' => 'draft-guide']);
    $doc = ProductDocument::factory()->published()->create(['product_id' => $product->id, 'title' => 'Getting started with LMS', 'slug' => 'getting-started', 'section' => 'Getting started', 'body' => '<p>Create your first pipeline.</p>']);
    ProductDocument::factory()->published()->create(['product_id' => $product->id, 'title' => 'Importing leads', 'slug' => 'importing-leads']);

    $this->get('/products/lms/docs/draft-guide')->assertNotFound();
    $this->get('/products/lms/docs')->assertOk()->assertSee('Getting started with LMS')->assertSee('Importing leads')->assertDontSee('Draft guide');

    $page = $this->get('/products/lms/docs/getting-started')->assertOk()
        ->assertSee('Create your first pipeline.')->assertSee('Importing leads')
        ->assertSee('<link rel="canonical" href="'.url('/products/lms/docs/getting-started').'"', false)
        ->assertSee('"@type":"TechArticle"', false)->assertSee('"BreadcrumbList"', false);

    expect($page->getContent())->toContain('Documentation');

    $locations = collect(app(SitemapGenerator::class)->entries())->pluck('loc');
    expect($locations)->toContain(url('/products/lms/docs/getting-started'))->toContain(url('/products/lms/docs'))
        ->not->toContain(url('/products/lms/docs/draft-guide'));

    SyncSearchEntry::dispatchSync('product_document', $doc->id);
    SyncSearchEntry::dispatchSync('product_document', $draft->id);
    expect(SearchEntry::query()->where('searchable_type', 'product_document')->pluck('url')->all())->toContain('/products/lms/docs/getting-started')->toContain('/products/lms/docs/importing-leads')->not->toContain('/products/lms/docs/draft-guide');

    $this->get(app(PreviewLink::class)->for($draft, 1))->assertOk()->assertSee('Draft guide')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

    // A hidden product hides its documentation too.
    $product->update(['status' => 'draft']);
    $this->get('/products/lms/docs/getting-started')->assertNotFound();
    expect(collect(app(SitemapGenerator::class)->entries())->pluck('loc'))->not->toContain(url('/products/lms/docs/getting-started'));
});

it('links documentation from the product page only when published documents exist', function () {
    $product = platformProduct();
    $this->get('/products/lms')->assertOk()->assertDontSee('/products/lms/docs');

    ProductDocument::factory()->published()->create(['product_id' => $product->id]);
    $this->get('/products/lms')->assertOk()->assertSee('/products/lms/docs');
    $this->get('/products/lms/docs')->assertOk();
});

it('manages documentation and feature capabilities in the product admin with publish gating', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
    $product = platformProduct();
    $module = ProductModule::factory()->create(['product_id' => $product->id, 'name' => 'Capture']);

    $this->actingAs(adminUser('Product Manager'));

    Livewire::test(DocumentsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
        ->callAction(TestAction::make('create')->table(), data: ['title' => 'Setup guide', 'slug' => 'setup', 'section' => 'Getting started', 'body' => '<p>Steps.</p>', 'status' => 'published', 'sort_order' => 1])
        ->assertHasNoActionErrors();

    $document = ProductDocument::query()->where('slug', 'setup')->sole();
    expect($document->isPublished())->toBeTrue()->and($document->published_at)->not->toBeNull();

    Livewire::test(FeaturesRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
        ->callAction(TestAction::make('create')->table(), data: ['title' => 'Web forms', 'product_module_id' => $module->id, 'sort_order' => 1, 'capabilities' => [['name' => 'Spam filtering', 'description' => 'Blocks bots.', 'sort_order' => 0]]])
        ->assertHasNoActionErrors();

    $feature = ProductFeature::query()->where('title', 'Web forms')->sole();
    expect($feature->product_module_id)->toBe($module->id)->and($feature->capabilities()->pluck('name')->all())->toBe(['Spam filtering']);

    // Editors without products.publish can only save drafts.
    $this->actingAs(adminUser('Editor'));
    expect(Gate::allows('publish', $product))->toBeFalse();
});

it('shows product interest from Phase 13 analytics in the product list', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
    $product = platformProduct();
    ConversionEvent::factory()->count(3)->create(['type' => ConversionEventType::PageViewed, 'path' => '/products/lms', 'entity_type' => 'product', 'entity_id' => $product->id, 'created_at' => now()->subDay()]);
    ConversionEvent::factory()->create(['type' => ConversionEventType::PageViewed, 'path' => '/products/lms', 'entity_type' => 'product', 'entity_id' => $product->id, 'created_at' => now()->subDays(45)]);
    Lead::factory()->create(['product_id' => $product->id]);

    $this->actingAs(adminUser('Product Manager'));

    Livewire::test(ListProducts::class)->assertCanSeeTableRecords([$product])->assertTableColumnStateSet('views_30d', 3, $product)->assertTableColumnStateSet('leads_30d', 1, $product);
});
