<?php

namespace Database\Factories;

use App\Models\GrievanceContact;
use App\Models\GrievanceLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GrievanceContact>
 */
class GrievanceContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'grievance_level_id' => GrievanceLevel::factory(),
            'name' => fake()->name(),
            'designation' => fake()->jobTitle(),
            'email' => fake()->safeEmail(),
            'phone' => '+91 98765 '.fake()->numerify('#####'),
            'address' => null,
            'sort_order' => 0,
        ];
    }
}
