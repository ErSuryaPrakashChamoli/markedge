<?php

namespace Database\Factories;

use App\Enums\CtaAction;
use App\Enums\CtaVariant;
use App\Models\Cta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cta>
 */
class CtaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(3, true)),
            'key' => fake()->unique()->slug(2),
            'headline' => fake()->sentence(5),
            'body' => fake()->sentence(),
            'primary_label' => 'Start a Conversation',
            'primary_action' => CtaAction::Url,
            'primary_value' => '/contact',
            'secondary_label' => null,
            'secondary_action' => null,
            'secondary_value' => null,
            'whatsapp_message' => null,
            'variant' => CtaVariant::Band,
            'is_active' => true,
        ];
    }

    public function whatsapp(): static
    {
        return $this->state([
            'primary_action' => CtaAction::Whatsapp,
            'primary_value' => null,
            'whatsapp_message' => 'Hi Markedge, I would like to discuss a {entity} requirement.',
        ]);
    }
}
