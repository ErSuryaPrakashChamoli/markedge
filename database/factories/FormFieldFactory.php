<?php

namespace Database\Factories;

use App\Enums\FormFieldType;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormField>
 */
class FormFieldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'key' => fake()->unique()->slug(1),
            'label' => ucfirst(fake()->words(2, true)),
            'type' => FormFieldType::Text,
            'placeholder' => null,
            'help_text' => null,
            'options' => null,
            'is_required' => false,
            'validation' => null,
            'width' => 'full',
            'sort_order' => 0,
            'maps_to' => null,
        ];
    }

    public function required(): static
    {
        return $this->state(['is_required' => true]);
    }

    public function select(array $options): static
    {
        return $this->state(['type' => FormFieldType::Select, 'options' => $options]);
    }
}
