<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'company' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'country' => fake()->country(),
            'city' => fake()->city(),
            'requirement' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'status' => LeadStatus::New,
            'custom_fields' => [],
            'first_source' => 'google',
            'first_medium' => 'organic',
            'first_landing_page' => '/',
            'first_visited_at' => now()->subDays(3),
            'last_source' => 'direct',
            'last_medium' => null,
            'last_landing_page' => '/contact',
            'last_visited_at' => now(),
            'visitor_id' => (string) Str::uuid(),
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'os' => 'Linux',
            'spam_score' => 0,
        ];
    }

    public function spam(): static
    {
        return $this->state(['status' => LeadStatus::Spam, 'spam_score' => 90]);
    }

    public function converted(): static
    {
        return $this->state(['status' => LeadStatus::Converted, 'closed_at' => now()]);
    }
}
