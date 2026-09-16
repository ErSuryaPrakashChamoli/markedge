<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->unique()->name(),
            'role_title' => fake()->jobTitle(),
            'bio' => fake()->paragraph(),
            'social_links' => [],
            'is_visible' => true,
        ];
    }
}
