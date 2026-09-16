<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => null,
            'author_name' => fake()->name(),
            'author_role' => fake()->jobTitle(),
            'company_name' => fake()->company(),
            'quote' => fake()->paragraph(),
            'product_id' => null,
            'service_id' => null,
            'is_visible' => false,
            'sort_order' => 0,
            'given_at' => null,
        ];
    }

    public function visible(): static
    {
        return $this->state(['is_visible' => true]);
    }
}
