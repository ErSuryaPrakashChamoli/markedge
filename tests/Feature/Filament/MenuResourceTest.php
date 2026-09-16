<?php

use App\Filament\Resources\Menus\MenuResource;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Menus\RelationManagers\ItemsRelationManager;
use App\Models\Menu;
use App\Models\Page;
use App\Services\Cms\MenuBuilder;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('creates a menu with a unique key', function () {
    $this->actingAs(adminUser('Website Manager'));

    Livewire::test(CreateMenu::class)->fillForm(['name' => 'Header', 'key' => 'header'])->call('create')->assertHasNoFormErrors();
    Livewire::test(CreateMenu::class)->fillForm(['name' => 'Header again', 'key' => 'header'])->call('create')->assertHasFormErrors(['key']);
});

it('adds an entity-linked item with automatic children and the frontend picks it up', function () {
    $menu = Menu::factory()->create(['key' => 'header']);
    $page = Page::factory()->published()->create(['title' => 'About', 'slug' => 'about']);
    $this->actingAs(adminUser('Website Manager'));

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $menu, 'pageClass' => EditMenu::class])
        ->callAction(TestAction::make('create')->table(), data: [
            'label' => 'About us', 'type' => 'entity', 'linkable_type' => 'page', 'linkable_id' => $page->id,
            'is_visible' => true, 'sort_order' => 1, 'settings' => ['auto_children' => null, 'mega_menu' => false],
        ])
        ->assertHasNoActionErrors();

    $tree = app(MenuBuilder::class)->build('header');

    expect($tree)->toHaveCount(1)
        ->and($tree[0]->label)->toBe('About us')
        ->and($tree[0]->url)->toBe('/about');
});

it('requires a URL for custom link items', function () {
    $menu = Menu::factory()->create(['key' => 'footer']);
    $this->actingAs(adminUser('Website Manager'));

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $menu, 'pageClass' => EditMenu::class])
        ->callAction(TestAction::make('create')->table(), data: ['label' => 'Careers', 'type' => 'url', 'url' => '', 'is_visible' => true, 'sort_order' => 0])
        ->assertHasActionErrors(['url']);
});

it('forbids content managers from editing navigation', function () {
    $menu = Menu::factory()->create(['key' => 'header']);
    $this->actingAs(adminUser('Content Manager'));

    $this->get(MenuResource::getUrl('edit', ['record' => $menu]))->assertForbidden();
});
