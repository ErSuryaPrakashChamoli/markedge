<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group' => 'company',
            'key' => 'company.'.fake()->unique()->slug(2),
            'value' => fake()->sentence(),
            'type' => 'text',
        ];
    }
}
