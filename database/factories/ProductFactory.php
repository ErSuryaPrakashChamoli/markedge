<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'tagline' => fake()->sentence(4),
            'product_type' => 'SaaS',
            'short_description' => fake()->paragraph(),
            'long_description' => '<p>'.fake()->paragraph().'</p>',
            'status' => ProductStatus::Draft,
            'benefits' => [],
            'use_cases' => [],
            'integrations' => [],
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => ProductStatus::Active, 'published_at' => now()->subMinute()]);
    }

    public function comingSoon(): static
    {
        return $this->state(['status' => ProductStatus::ComingSoon]);
    }

    public function archived(): static
    {
        return $this->state(['status' => ProductStatus::Archived]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
