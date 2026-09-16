<?php

namespace Database\Factories;

use App\Enums\FormSuccessMode;
use App\Enums\FormType;
use App\Models\Form;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(2, true)).' form',
            'key' => fake()->unique()->slug(2),
            'type' => FormType::GeneralEnquiry,
            'heading' => fake()->sentence(4),
            'intro' => fake()->sentence(),
            'submit_label' => 'Send enquiry',
            'success_mode' => FormSuccessMode::Message,
            'success_message' => 'Thank you. We will be in touch shortly.',
            'notify_emails' => [],
            'auto_reply_enabled' => false,
            'core_fields' => [
                'name' => ['enabled' => true, 'required' => true, 'sort_order' => 1],
                'email' => ['enabled' => true, 'required' => true, 'sort_order' => 2],
                'phone' => ['enabled' => true, 'required' => false, 'sort_order' => 3],
                'message' => ['enabled' => true, 'required' => false, 'sort_order' => 4],
            ],
            'is_active' => true,
            'honeypot_enabled' => true,
            'requires_consent' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function ofType(FormType $type): static
    {
        return $this->state(['type' => $type]);
    }
}
