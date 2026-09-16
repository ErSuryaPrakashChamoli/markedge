<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_category_id' => ServiceCategory::factory(),
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'tagline' => fake()->sentence(6),
            'short_description' => fake()->paragraph(),
            'overview' => '<p>'.fake()->paragraph().'</p>',
            'benefits' => [['title' => fake()->sentence(3), 'text' => fake()->sentence()]],
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

    public function scheduled(): static
    {
        return $this->state(['status' => PublishStatus::Scheduled, 'published_at' => now()->addDay()]);
    }

    public function archived(): static
    {
        return $this->state(['status' => PublishStatus::Archived, 'published_at' => now()->subMonth()]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
