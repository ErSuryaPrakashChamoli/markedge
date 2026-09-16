<?php

namespace Database\Seeders;

use App\Models\ArticleCategory;
use Illuminate\Database\Seeder;

class ArticleCategorySeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Technology', 'AI', 'Software', 'Cybersecurity', 'Cloud', 'Digital Marketing', 'SEO',
            'Business Automation', 'Sales Technology', 'Recruitment Technology', 'Product Development',
        ];

        foreach ($names as $index => $name) {
            ArticleCategory::query()->updateOrCreate(
                ['slug' => str($name)->slug()->toString()],
                ['name' => $name, 'sort_order' => $index],
            );
        }
    }
}
