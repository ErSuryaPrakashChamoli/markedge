<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Product;
use App\Models\ProductDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductDocument>
 */
class ProductDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'title' => ucfirst(fake()->words(3, true)),
            'section' => 'Getting started',
            'excerpt' => fake()->sentence(),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'sort_order' => 0,
            'status' => PublishStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => PublishStatus::Published, 'published_at' => now()->subMinute()]);
    }
}
