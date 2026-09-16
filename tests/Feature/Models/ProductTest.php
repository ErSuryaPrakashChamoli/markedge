<?php

use App\Models\Product;
use App\Models\ProductFeature;
use App\Models\ProductModule;

it('lists active and coming-soon products as publicly visible', function () {
    $active = Product::factory()->active()->create();
    $comingSoon = Product::factory()->comingSoon()->create();
    Product::factory()->create();
    Product::factory()->archived()->create();

    expect(Product::publiclyVisible()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$active->id, $comingSoon->id])->sort()->values()->all());
});

it('removes features and modules when the product is force deleted', function () {
    $product = Product::factory()->create();
    ProductFeature::factory()->for($product)->create();
    ProductModule::factory()->for($product)->create();

    $product->forceDelete();

    expect(ProductFeature::count())->toBe(0)
        ->and(ProductModule::count())->toBe(0);
});

it('orders features by sort order', function () {
    $product = Product::factory()->create();
    $second = ProductFeature::factory()->for($product)->create(['sort_order' => 2]);
    $first = ProductFeature::factory()->for($product)->create(['sort_order' => 1]);

    expect($product->features->pluck('id')->all())->toBe([$first->id, $second->id]);
});
