<?php

namespace Database\Factories;

use App\Models\ProductCapability;
use App\Models\ProductFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductCapability>
 */
class ProductCapabilityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_feature_id' => ProductFeature::factory(),
            'name' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }
}
