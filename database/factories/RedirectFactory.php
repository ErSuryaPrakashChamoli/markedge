<?php

namespace Database\Factories;

use App\Enums\RedirectStatus;
use App\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redirect>
 */
class RedirectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'from_path' => '/old/'.fake()->unique()->slug(2),
            'to_url' => '/new/'.fake()->slug(2),
            'status_code' => RedirectStatus::MovedPermanently,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
