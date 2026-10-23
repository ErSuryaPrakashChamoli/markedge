<?php

namespace Database\Factories;

use App\Enums\SubscriberStatus;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => null,
            'status' => SubscriberStatus::Subscribed,
            'source_path' => '/',
            'consent_text' => 'I agree to receive the newsletter.',
            'consent_given_at' => now(),
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ];
    }

    public function unsubscribed(): static
    {
        return $this->state(['status' => SubscriberStatus::Unsubscribed, 'unsubscribed_at' => now()]);
    }
}
