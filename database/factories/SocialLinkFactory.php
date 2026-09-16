<?php

namespace Database\Factories;

use App\Models\SocialLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialLink>
 */
class SocialLinkFactory extends Factory
{
    public function definition(): array
    {
        $platform = fake()->randomElement(['linkedin', 'x', 'facebook', 'instagram', 'youtube']);

        return [
            'platform' => $platform,
            'label' => ucfirst($platform),
            'url' => "https://{$platform}.com/markedge",
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }

    public function hidden(): static
    {
        return $this->state(['is_visible' => false]);
    }
}
