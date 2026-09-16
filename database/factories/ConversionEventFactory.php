<?php

namespace Database\Factories;

use App\Enums\ConversionEventType;
use App\Models\ConversionEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConversionEvent>
 */
class ConversionEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => ConversionEventType::CtaClicked,
            'path' => '/services/'.fake()->slug(2),
            'visitor_id' => (string) Str::uuid(),
            'created_at' => now(),
        ];
    }
}
