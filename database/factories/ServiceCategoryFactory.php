<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceCategory>
 */
class ServiceCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'pillar_label' => strtoupper(fake()->word()),
            'tagline' => fake()->sentence(6),
            'short_description' => fake()->paragraph(),
            'status' => PublishStatus::Draft,
            'published_at' => null,
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => PublishStatus::Published, 'published_at' => now()->subMinute()]);
    }
}
