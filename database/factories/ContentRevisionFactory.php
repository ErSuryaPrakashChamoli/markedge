<?php

namespace Database\Factories;

use App\Models\ContentRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentRevision>
 */
class ContentRevisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'version' => 1,
            'snapshot' => ['attributes' => ['title' => fake()->sentence(3)], 'seo' => null, 'media' => [], 'relations' => []],
            'checksum' => fake()->sha256(),
            'reason' => null,
            'created_at' => now(),
        ];
    }
}
