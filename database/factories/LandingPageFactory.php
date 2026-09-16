<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\LandingPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LandingPage>
 */
class LandingPageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->words(3, true)),
            'campaign_id' => null,
            'form_id' => null,
            'cta_id' => null,
            'blocks' => [],
            'hide_navigation' => true,
            'hide_footer_links' => true,
            'tracking' => null,
            'status' => PublishStatus::Draft,
            'published_at' => null,
            'expires_at' => null,
            'expired_redirect_url' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => PublishStatus::Published, 'published_at' => now()->subMinute()]);
    }

    public function expired(): static
    {
        return $this->published()->state(['expires_at' => now()->subDay(), 'expired_redirect_url' => '/']);
    }
}
