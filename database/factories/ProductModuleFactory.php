<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductModule>
 */
class ProductModuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => ucfirst(fake()->words(2, true)),
            'summary' => fake()->sentence(),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'highlights' => [fake()->sentence(3), fake()->sentence(3)],
            'sort_order' => 0,
        ];
    }
}
