<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Industry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Industry>
 */
class IndustryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'tagline' => fake()->sentence(6),
            'short_description' => fake()->paragraph(),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'challenges' => [],
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

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
