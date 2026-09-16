<?php

namespace Database\Seeders;

use App\Enums\MenuItemType;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Header, footer and legal menus. Product and service children are attached
 * automatically at render time through the auto_children setting.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHeader();
        $this->seedFooter();
        $this->seedLegal();
    }

    protected function seedHeader(): void
    {
        $menu = Menu::query()->updateOrCreate(['key' => 'header'], ['name' => 'Header']);

        if ($menu->items()->exists()) {
            return;
        }

        $whatWeDo = $this->item($menu, null, 'What We Do', 0, url: '/services', settings: ['mega_menu' => true]);

        foreach (ServiceCategory::query()->ordered()->get() as $index => $category) {
            $this->item($menu, $whatWeDo, $category->name, $index, linkable: $category, settings: ['auto_children' => 'service_category']);
        }

        $solutions = $this->item($menu, null, 'Solutions', 1, url: '/solutions');
        $this->item($menu, $solutions, 'By Business Need', 0, url: '/solutions', settings: ['auto_children' => 'solutions']);
        $this->item($menu, $solutions, 'By Industry', 1, url: '/industries', settings: ['auto_children' => 'industries']);

        $products = $this->item($menu, null, 'Products', 2, url: '/products', settings: ['auto_children' => 'products']);
        $this->item($menu, $products, 'View All Products', 99, url: '/products');

        $work = $this->item($menu, null, 'Work', 3, url: '/case-studies');
        $this->item($menu, $work, 'Case Studies', 0, url: '/case-studies');

        $insights = $this->item($menu, null, 'Insights', 4, url: '/insights', settings: ['auto_children' => 'article_categories']);
        $this->item($menu, $insights, 'All Articles', 0, url: '/insights');

        $this->item($menu, null, 'About', 5, linkable: Page::query()->where('slug', 'about')->first(), url: '/about');
    }

    protected function seedFooter(): void
    {
        $menu = Menu::query()->updateOrCreate(['key' => 'footer'], ['name' => 'Footer']);

        if ($menu->items()->exists()) {
            return;
        }

        $columns = [
            'Company' => [
                ['About', '/about'], ['Careers', '/careers'], ['Contact', '/contact'], ['Insights', '/insights'],
            ],
            'Technology' => [
                ['Software Development', '/services/software-development'],
                ['Web Development', '/services/web-development'],
                ['Mobile Apps', '/services/mobile-app-development'],
                ['Product Development', '/services/product-development'],
            ],
            'IT Infrastructure' => [
                ['IT AMC', '/services/it-amc'], ['Networking', '/services/networking'],
                ['Cloud', '/services/cloud'], ['Cybersecurity', '/services/cybersecurity'],
            ],
            'Digital Growth' => [
                ['Digital Marketing', '/services/digital-marketing'], ['SEO', '/services/seo'],
                ['Social Media', '/services/social-media-marketing'], ['Performance Marketing', '/services/performance-marketing'],
            ],
            'Products' => [
                ['Lead Management System', '/products/lead-management-system'],
                ['Recruitment Management System', '/products/recruitment-management-system'],
                ['All Products', '/products'],
            ],
        ];

        $columnIndex = 0;

        foreach ($columns as $heading => $links) {
            $column = $this->item($menu, null, $heading, $columnIndex++, type: MenuItemType::Heading);

            foreach ($links as $index => [$label, $url]) {
                $this->item($menu, $column, $label, $index, url: $url);
            }
        }
    }

    protected function seedLegal(): void
    {
        $menu = Menu::query()->updateOrCreate(['key' => 'legal'], ['name' => 'Legal']);

        if ($menu->items()->exists()) {
            return;
        }

        foreach ([['Privacy Policy', '/privacy-policy'], ['Terms', '/terms'], ['Cookie Policy', '/cookie-policy']] as $index => [$label, $url]) {
            $this->item($menu, null, $label, $index, url: $url);
        }
    }

    protected function item(
        Menu $menu,
        ?MenuItem $parent,
        string $label,
        int $sortOrder,
        ?string $url = null,
        ?Model $linkable = null,
        MenuItemType $type = MenuItemType::Url,
        array $settings = [],
    ): MenuItem {
        return $menu->items()->create([
            'parent_id' => $parent?->id,
            'label' => $label,
            'type' => $linkable ? MenuItemType::Entity : $type,
            'linkable_type' => $linkable?->getMorphClass(),
            'linkable_id' => $linkable?->getKey(),
            'url' => $url,
            'sort_order' => $sortOrder,
            'settings' => $settings ?: null,
        ]);
    }
}
