<?php

namespace Database\Factories;

use App\Models\Faq;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'faqable_type' => 'service',
            'faqable_id' => Service::factory(),
            'question' => fake()->sentence().'?',
            'answer' => '<p>'.fake()->paragraph().'</p>',
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }
}
