<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\CaseStudy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseStudy>
 */
class CaseStudyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => null,
            'industry_id' => null,
            'title' => ucfirst(fake()->unique()->sentence(4)),
            'excerpt' => fake()->paragraph(),
            'challenge' => '<p>'.fake()->paragraph().'</p>',
            'solution' => '<p>'.fake()->paragraph().'</p>',
            'implementation' => null,
            'results' => null,
            'outcomes' => [
                ['label' => 'Manual reporting', 'value' => 'Eliminated', 'kind' => 'qualitative'],
            ],
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
