<?php

namespace Database\Factories;

use App\Models\Cta;
use App\Models\CtaClick;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CtaClick>
 */
class CtaClickFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cta_id' => Cta::factory(),
            'action' => 'whatsapp',
            'path' => '/services/'.fake()->slug(2),
            'campaign_id' => null,
            'utm' => null,
            'created_at' => now(),
        ];
    }
}
