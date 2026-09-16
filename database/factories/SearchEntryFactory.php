<?php

namespace Database\Factories;

use App\Models\SearchEntry;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchEntry>
 */
class SearchEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'searchable_type' => 'service',
            'searchable_id' => Service::factory(),
            'kind' => 'service',
            'title' => fake()->sentence(3),
            'summary' => fake()->sentence(),
            'body_text' => fake()->paragraph(),
            'url' => '/services/'.fake()->slug(2),
            'published_at' => now(),
        ];
    }
}
