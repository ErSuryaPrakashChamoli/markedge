<?php

use App\Cms\Blocks\BlockRegistry;
use App\Models\Page;
use Illuminate\Support\Facades\Storage;

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
    Storage::fake('public');
    Storage::disk('public')->put('blocks/hero/one.jpg', 'image');

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

it('drops a slide whose uploaded picture is missing instead of rendering a broken image', function () {
    Storage::fake('public');
    Storage::disk('public')->put('blocks/hero/kept.jpg', 'image');

    Page::factory()->home()->create(['status' => 'published', 'published_at' => now()->subMinute(), 'blocks' => [
        ['type' => 'hero', 'data' => ['headline' => 'Technology that moves business forward.', 'variant' => 'carousel', 'slides' => [
            ['image' => 'blocks/hero/lost.png', 'image_alt' => 'Lost upload', 'heading' => 'Lost highlight'],
            ['image' => 'blocks/hero/kept.jpg', 'image_alt' => 'Kept upload', 'heading' => 'Kept highlight'],
        ]]],
    ]]);

    $this->get('/')->assertOk()
        ->assertSee('carousel({ count: 1, autoplay: 6 })', false)
        ->assertSee('/storage/blocks/hero/kept.jpg', false)
        ->assertDontSee('blocks/hero/lost.png', false)
        ->assertDontSee('Lost highlight');
});

it('links uploaded slide pictures from the site root so they load on whichever host serves the page', function () {
    Storage::fake('public', ['url' => config('filesystems.disks.public.url')]);
    Storage::disk('public')->put('blocks/hero/one.jpg', 'image');

    Page::factory()->home()->create(['status' => 'published', 'published_at' => now()->subMinute(), 'blocks' => [
        ['type' => 'hero', 'data' => ['headline' => 'Technology that moves business forward.', 'variant' => 'carousel', 'slides' => [
            ['image' => 'blocks/hero/one.jpg', 'image_alt' => 'First slide', 'heading' => 'Our first highlight'],
        ]]],
    ]]);

    $this->get('http://127.0.0.1:8000/')->assertOk()
        ->assertSee('src="/storage/blocks/hero/one.jpg"', false);
});
