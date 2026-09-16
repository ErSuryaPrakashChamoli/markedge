<?php

use App\Enums\ProductStatus;
use App\Filament\RelationManagers\FaqsRelationManager;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Article;
use App\Models\Industry;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('lets a product manager create a new product without any code', function () {
    $this->actingAs(adminUser('Product Manager'));

    Livewire::test(CreateProduct::class)
        ->fillForm(['name' => 'Inventory Management System', 'tagline' => 'Stock, simplified.', 'status' => ProductStatus::ComingSoon->value])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::query()->where('slug', 'inventory-management-system')->first();

    expect($product)->not->toBeNull()
        ->and($product->status)->toBe(ProductStatus::ComingSoon)
        ->and($product->isPubliclyVisible())->toBeTrue();
});

it('offers only the draft status to users who cannot publish products', function () {
    $this->actingAs(adminUser('Content Manager'));

    expect(array_keys(ProductResource::statusOptions(null)))->toBe(['draft']);
});

it('links a product to industries and solutions from the form', function () {
    $industry = Industry::factory()->create();
    $solution = Solution::factory()->create();
    $product = Product::factory()->create();
    $this->actingAs(adminUser('Product Manager'));

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['industries' => [$industry->id], 'solutions' => [$solution->id]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh()->industries->pluck('id')->all())->toBe([$industry->id])
        ->and($solution->fresh()->products->pluck('id')->all())->toBe([$product->id]);
});

it('rejects a service slug already used by a service category', function () {
    $category = ServiceCategory::factory()->create(['slug' => 'technology']);
    $this->actingAs(adminUser());

    Livewire::test(CreateService::class)
        ->fillForm(['service_category_id' => $category->id, 'name' => 'Technology', 'slug' => 'technology'])
        ->call('create')
        ->assertHasFormErrors(['slug']);

    Livewire::test(CreateService::class)
        ->fillForm(['service_category_id' => $category->id, 'name' => 'Cloud Solutions', 'slug' => 'cloud-solutions'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Service::query()->where('slug', 'cloud-solutions')->exists())->toBeTrue();
});

it('manages FAQs on a service through the relation manager', function () {
    $service = Service::factory()->create();
    $this->actingAs(adminUser());

    Livewire::test(FaqsRelationManager::class, ['ownerRecord' => $service, 'pageClass' => EditService::class])
        ->callAction(TestAction::make('create')->table(), data: ['question' => 'How long does a project take?', 'answer' => '<p>It depends.</p>', 'is_visible' => true, 'sort_order' => 1])
        ->assertHasNoActionErrors();

    expect($service->faqs()->count())->toBe(1)
        ->and(DB::table('faqs')->value('faqable_type'))->toBe('service');
});

it('creates an article in review for a content manager and computes reading time', function () {
    $this->actingAs(adminUser('Content Manager'));

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Why automation matters',
            'excerpt' => 'A short summary.',
            'body' => '<p>'.str_repeat('word ', 450).'</p>',
            'status' => 'review',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::query()->where('slug', 'why-automation-matters')->first();

    expect($article)->not->toBeNull()
        ->and($article->reading_time_minutes)->toBe(3)
        ->and($article->status->value)->toBe('review');
});

it('prevents a content manager from creating an article as published', function () {
    $this->actingAs(adminUser('Content Manager'));

    Livewire::test(CreateArticle::class)
        ->fillForm(['title' => 'Sneaky publish', 'excerpt' => 'x', 'body' => '<p>x</p>', 'status' => 'published'])
        ->call('create')
        ->assertHasFormErrors(['status']);
});
