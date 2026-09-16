<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->sentence(5)),
            'excerpt' => fake()->paragraph(),
            'body' => '<p>'.implode('</p><p>', fake()->paragraphs(3)).'</p>',
            'author_id' => Author::factory(),
            'article_category_id' => ArticleCategory::factory(),
            'is_featured' => false,
            'status' => PublishStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => PublishStatus::Published, 'published_at' => now()->subMinute()]);
    }

    public function scheduled(): static
    {
        return $this->state(['status' => PublishStatus::Scheduled, 'published_at' => now()->addDay()]);
    }

    public function inReview(): static
    {
        return $this->state(['status' => PublishStatus::Review]);
    }
}
