<?php

namespace Database\Factories;

use App\Enums\FollowUpType;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadFollowUp>
 */
class LeadFollowUpFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'user_id' => User::factory(),
            'created_by' => null,
            'type' => FollowUpType::Call,
            'due_at' => now()->addDay(),
            'note' => fake()->sentence(),
        ];
    }

    public function overdue(): static
    {
        return $this->state(['due_at' => now()->subDay()]);
    }

    public function completed(): static
    {
        return $this->state(['completed_at' => now(), 'outcome' => 'Done.']);
    }
}
