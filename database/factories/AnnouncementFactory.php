<?php

namespace Database\Factories;

use App\Enums\AnnouncementDisplay;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'message' => fake()->sentence(),
            'link_label' => null,
            'link_url' => null,
            'display' => AnnouncementDisplay::Bar,
            'style' => 'dark',
            'is_active' => false,
            'is_dismissible' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function expired(): static
    {
        return $this->state(['is_active' => true, 'ends_at' => now()->subDay()]);
    }

    public function upcoming(): static
    {
        return $this->state(['is_active' => true, 'starts_at' => now()->addDay()]);
    }
}
