<?php

namespace Database\Factories;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->words(3, true)),
            'template' => PageTemplate::Default,
            'excerpt' => fake()->sentence(),
            'blocks' => [
                ['type' => 'rich_text', 'data' => ['is_enabled' => true, 'body' => '<p>'.fake()->paragraph().'</p>']],
            ],
            'status' => PublishStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => PublishStatus::Published, 'published_at' => now()->subMinute()]);
    }

    public function home(): static
    {
        return $this->state(['title' => 'Home', 'slug' => Page::HOME_SLUG, 'template' => PageTemplate::Home]);
    }
}
