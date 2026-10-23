<?php

namespace Database\Factories;

use App\Enums\SocialPlatform;
use App\Models\SocialVideo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialVideo>
 */
class SocialVideoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->sentence(4)),
            'platform' => SocialPlatform::YouTube,
            'url' => 'https://www.youtube.com/watch?v='.fake()->regexify('[A-Za-z0-9_-]{11}'),
            'caption' => fake()->sentence(),
            'is_vertical' => false,
            'posted_at' => now()->subDays(fake()->numberBetween(1, 60)),
            'is_featured' => false,
            'is_visible' => false,
            'sort_order' => 0,
        ];
    }

    public function visible(): static
    {
        return $this->state(['is_visible' => true]);
    }

    public function instagram(): static
    {
        return $this->state([
            'platform' => SocialPlatform::Instagram,
            'url' => 'https://www.instagram.com/reel/'.fake()->regexify('[A-Za-z0-9]{11}').'/',
            'is_vertical' => true,
        ]);
    }

    public function linkedIn(): static
    {
        return $this->state([
            'platform' => SocialPlatform::LinkedIn,
            'url' => 'https://www.linkedin.com/posts/markedge_update-activity-'.fake()->numerify('7###############').'-abcd',
        ]);
    }
}
