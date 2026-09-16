<?php

namespace Database\Factories;

use App\Enums\EditorialCommentType;
use App\Models\EditorialComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditorialComment>
 */
class EditorialCommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => EditorialCommentType::Comment,
            'body' => fake()->sentence(),
        ];
    }

    public function changeRequest(): static
    {
        return $this->state(['type' => EditorialCommentType::ChangeRequest]);
    }
}
