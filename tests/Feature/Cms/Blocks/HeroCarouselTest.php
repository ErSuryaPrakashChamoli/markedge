<?php

use App\Cms\Blocks\BlockRegistry;
use App\Models\Page;

it('validates carousel slides against the preset vocabulary', function () {
    $registry = app(BlockRegistry::class);

    $valid = $registry->validate([['type' => 'hero', 'data' => ['headline' => 'Hi', 'variant' => 'carousel', 'autoplay_seconds' => 6, 'slides' => [
        ['image' => 'blocks/hero/a.jpg', 'image_alt' => 'Team', 'heading' => 'Build', 'button_label' => 'See work', 'button_url' => '/case-studies', 'text_size' => 'lg', 'text_color' => 'white', 'text_position' => 'bottom-left', 'button_style' => 'primary'],
    ]]]], 'page');
    expect($valid->isEmpty())->toBeTrue();

    $invalid = $registry->validate([['type' => 'hero', 'data' => ['headline' => 'Hi', 'variant' => 'carousel', 'slides' => [
        ['image_alt' => 'Missing image', 'text_color' => '#ff0000', 'text_position' => 'left: 40px', 'button_url' => 'javascript:alert(1)'],
    ]]]], 'page');
    expect($invalid->isEmpty())->toBeFalse();
    expect(implode(' ', $invalid->all()))->toContain('slides.0.image')->toContain('slides.0.text_color')->toContain('slides.0.text_position')->toContain('slides.0.button_url');
});

it('renders the hero as a 60/40 split with the admin slides on the right', function () {
    Page::factory()->home()->create(['status' => 'published', 'published_at' => now()->subMinute(), 'blocks' => [
        ['type' => 'hero', 'data' => ['headline' => 'Technology that moves business forward.', 'variant' => 'carousel', 'autoplay_seconds' => 4, 'slide_ratio' => 'square', 'slides' => [
            ['image' => 'blocks/hero/one.jpg', 'image_alt' => 'First slide', 'heading' => 'Our first highlight', 'subheading' => 'Short line', 'button_label' => 'Explore', 'button_url' => '/services', 'text_size' => 'lg', 'text_color' => 'white', 'text_position' => 'bottom-left', 'button_style' => 'primary'],
            ['image' => 'https://cdn.example.test/two.jpg', 'image_alt' => 'Second slide', 'heading' => 'Second highlight', 'text_color' => 'dark', 'text_position' => 'top-right', 'button_label' => 'Docs', 'button_url' => 'https://example.test/docs', 'button_style' => 'light'],
        ]]],
    ]]);

    $this->get('/')->assertOk()
        ->assertSee('lg:grid-cols-[3fr_2fr]', false)
        ->assertSee('carousel({ count: 2, autoplay: 4 })', false)
        ->assertSee('aspect-square', false)
        ->assertSee('/storage/blocks/hero/one.jpg', false)
        ->assertSee('https://cdn.example.test/two.jpg', false)
        ->assertSee('Our first highlight')->assertSee('Second highlight')
        ->assertSee('items-end justify-start', false)->assertSee('items-start justify-end', false)
        ->assertSee('Slide 2')->assertSee('target="_blank"', false);
});
