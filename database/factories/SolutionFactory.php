<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Solution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Solution>
 */
class SolutionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'tagline' => fake()->sentence(6),
            'short_description' => fake()->paragraph(),
            'problem_statement' => '<p>'.fake()->paragraph().'</p>',
            'approach' => '<p>'.fake()->paragraph().'</p>',
            'outcomes' => [],
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
