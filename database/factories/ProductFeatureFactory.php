<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductFeature>
 */
class ProductFeatureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'title' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'icon' => null,
            'group_label' => null,
            'sort_order' => 0,
        ];
    }
}
