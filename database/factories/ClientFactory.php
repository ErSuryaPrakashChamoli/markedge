<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'website_url' => fake()->url(),
            'industry_id' => null,
            'description' => fake()->sentence(),
            'is_visible' => false,
            'show_in_logo_cloud' => false,
            'sort_order' => 0,
        ];
    }

    public function visible(): static
    {
        return $this->state(['is_visible' => true]);
    }

    public function inLogoCloud(): static
    {
        return $this->state(['is_visible' => true, 'show_in_logo_cloud' => true]);
    }
}
