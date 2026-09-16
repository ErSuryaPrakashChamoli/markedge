<?php

use App\Enums\MenuItemType;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Cms\MenuBuilder;

it('returns an empty tree for an unknown menu', function () {
    expect(app(MenuBuilder::class)->build('missing'))->toBe([]);
});

it('builds nested nodes in sort order and skips hidden items', function () {
    $menu = Menu::factory()->create(['key' => 'header']);
    $parent = MenuItem::factory()->for($menu)->create(['label' => 'What We Do', 'sort_order' => 1]);
    MenuItem::factory()->for($menu)->create(['label' => 'About', 'sort_order' => 0]);
    MenuItem::factory()->for($menu)->create(['label' => 'Second child', 'parent_id' => $parent->id, 'sort_order' => 2]);
    MenuItem::factory()->for($menu)->create(['label' => 'First child', 'parent_id' => $parent->id, 'sort_order' => 1]);
    MenuItem::factory()->for($menu)->hidden()->create(['label' => 'Hidden', 'sort_order' => 3]);

    $tree = app(MenuBuilder::class)->build('header');

    expect(collect($tree)->pluck('label')->all())->toBe(['About', 'What We Do'])
        ->and(collect($tree[1]->children)->pluck('label')->all())->toBe(['First child', 'Second child']);
});

it('resolves entity links and drops unpublished targets', function () {
    $menu = Menu::factory()->create(['key' => 'header']);
    $published = Page::factory()->published()->create(['slug' => 'about']);
    $draft = Page::factory()->create(['slug' => 'careers']);
    MenuItem::factory()->for($menu)->create(['label' => 'About', 'type' => MenuItemType::Entity, 'linkable_type' => 'page', 'linkable_id' => $published->id, 'url' => null]);
    MenuItem::factory()->for($menu)->create(['label' => 'Careers', 'type' => MenuItemType::Entity, 'linkable_type' => 'page', 'linkable_id' => $draft->id, 'url' => null]);

    $tree = app(MenuBuilder::class)->build('header');

    expect($tree)->toHaveCount(1)
        ->and($tree[0]->url)->toBe('/about');
});

it('appends publicly visible products as automatic children', function () {
    $menu = Menu::factory()->create(['key' => 'header']);
    MenuItem::factory()->for($menu)->create(['label' => 'Products', 'url' => '/products', 'settings' => ['auto_children' => 'products']]);
    Product::factory()->active()->create(['name' => 'Lead Management System', 'sort_order' => 1]);
    Product::factory()->comingSoon()->create(['name' => 'Future Product', 'sort_order' => 2]);
    Product::factory()->create(['name' => 'Draft Product']);

    $children = app(MenuBuilder::class)->build('header')[0]->children;

    expect(collect($children)->pluck('label')->all())->toBe(['Lead Management System', 'Future Product'])
        ->and($children[1]->badge)->toBe('Coming soon')
        ->and($children[0]->url)->toBe('/products/lead-management-system');
});

it('appends the published services of a linked category', function () {
    $menu = Menu::factory()->create(['key' => 'header']);
    $category = ServiceCategory::factory()->published()->create(['slug' => 'technology']);
    Service::factory()->for($category, 'category')->published()->create(['name' => 'Web Development']);
    Service::factory()->for($category, 'category')->create(['name' => 'Draft Service']);
    MenuItem::factory()->for($menu)->create([
        'label' => 'Technology', 'type' => MenuItemType::Entity, 'linkable_type' => 'service_category',
        'linkable_id' => $category->id, 'url' => null, 'settings' => ['auto_children' => 'service_category'],
    ]);

    $node = app(MenuBuilder::class)->build('header')[0];

    expect($node->url)->toBe('/services/technology')
        ->and(collect($node->children)->pluck('label')->all())->toBe(['Web Development']);
});

it('marks the current section and its parent as active', function () {
    $menu = Menu::factory()->create(['key' => 'header']);
    $parent = MenuItem::factory()->for($menu)->create(['label' => 'Services', 'url' => '/services']);
    MenuItem::factory()->for($menu)->create(['label' => 'SEO', 'url' => '/services/seo', 'parent_id' => $parent->id]);
    MenuItem::factory()->for($menu)->create(['label' => 'About', 'url' => '/about']);

    $tree = app(MenuBuilder::class)->forPath('header', 'services/seo/');

    expect($tree[0]->isActive)->toBeTrue()
        ->and($tree[0]->children[0]->isActive)->toBeTrue()
        ->and($tree[1]->isActive)->toBeFalse();
});

it('does not treat the home link as active on other pages', function () {
    $menu = Menu::factory()->create(['key' => 'header']);
    MenuItem::factory()->for($menu)->create(['label' => 'Home', 'url' => '/']);

    expect(app(MenuBuilder::class)->forPath('header', 'about')[0]->isActive)->toBeFalse();
});

it('rebuilds the tree after menu content changes', function () {
    $menu = Menu::factory()->create(['key' => 'header']);
    MenuItem::factory()->for($menu)->create(['label' => 'Before']);

    expect(app(MenuBuilder::class)->build('header')[0]->label)->toBe('Before');

    $menu->items()->first()->update(['label' => 'After']);

    expect(app(MenuBuilder::class)->build('header')[0]->label)->toBe('After');
});
