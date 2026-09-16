<?php

namespace Database\Factories;

use App\Enums\CampaignChannel;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        $code = fake()->unique()->slug(3);

        return [
            'name' => ucfirst(str_replace('-', ' ', $code)),
            'utm_source' => 'linkedin',
            'utm_medium' => 'paid_social',
            'utm_campaign' => $code,
            'channel' => CampaignChannel::PaidSocial,
            'status' => CampaignStatus::Planned,
            'starts_at' => null,
            'ends_at' => null,
            'tracking' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => CampaignStatus::Active, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
    }

    public function ended(): static
    {
        return $this->state(['status' => CampaignStatus::Ended, 'starts_at' => now()->subMonths(2), 'ends_at' => now()->subDay()]);
    }
}
