<?php

namespace Database\Factories;

use App\Models\GrievanceLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GrievanceLevel>
 */
class GrievanceLevelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Customer Support', 'Grievance Officer', 'Nodal Officer']),
            'description' => fake()->sentence(),
            'response_time' => 'Within '.fake()->randomElement([2, 5, 7]).' working days',
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }

    public function hidden(): static
    {
        return $this->state(['is_visible' => false]);
    }
}
