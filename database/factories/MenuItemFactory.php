<?php

namespace Database\Factories;

use App\Enums\MenuItemType;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'parent_id' => null,
            'label' => fake()->words(2, true),
            'type' => MenuItemType::Url,
            'url' => '/'.fake()->slug(1),
            'is_visible' => true,
            'open_in_new_tab' => false,
            'sort_order' => 0,
        ];
    }

    public function heading(): static
    {
        return $this->state(['type' => MenuItemType::Heading, 'url' => null]);
    }

    public function hidden(): static
    {
        return $this->state(['is_visible' => false]);
    }
}
