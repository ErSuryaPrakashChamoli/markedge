<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoMeta>
 */
class SeoMetaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seoable_type' => 'page',
            'seoable_id' => Page::factory(),
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'canonical_url' => null,
            'robots_index' => true,
            'robots_follow' => true,
            'schema_overrides' => null,
            'include_in_sitemap' => true,
        ];
    }

    public function noindex(): static
    {
        return $this->state(['robots_index' => false, 'include_in_sitemap' => false]);
    }
}
