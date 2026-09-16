<?php

use App\Cms\Blocks\Block;
use App\Cms\Blocks\BlockRegistry;
use App\Rules\ValidBlocks;
use Illuminate\Support\Facades\Validator;

it('registers every block type with a unique key', function () {
    $registry = app(BlockRegistry::class);

    expect($registry->all())->toHaveCount(count(BlockRegistry::TYPES))
        ->and(array_keys($registry->all()))->toContain('hero', 'split_content', 'service_grid', 'product_showcase', 'solution_grid', 'industry_grid', 'technology_grid', 'stats', 'logo_cloud', 'process', 'timeline', 'case_study_grid', 'testimonials', 'faq', 'cta', 'rich_text', 'image_content', 'video', 'article_grid', 'related_services', 'related_products', 'related_content', 'lead_form', 'contact_form');
});

it('limits blocks to their allowed hosts', function () {
    $registry = app(BlockRegistry::class);

    expect(array_keys($registry->forHost('page')))->toContain('hero', 'contact_form')
        ->and(array_keys($registry->forHost('service')))->not->toContain('hero', 'contact_form')
        ->and(array_keys($registry->forHost('service')))->toContain('related_products', 'faq');
});

it('rejects unknown block types', function () {
    $errors = app(BlockRegistry::class)->validate([['type' => 'raw_html', 'data' => ['html' => '<script>']]], 'page');

    expect($errors->first('blocks.0'))->toContain('unknown type');
});

it('rejects blocks that are not allowed on the host', function () {
    $errors = app(BlockRegistry::class)->validate([['type' => 'hero', 'data' => ['headline' => 'Hi']]], 'service');

    expect($errors->first('blocks.0'))->toContain('cannot be used here');
});

it('validates block data against the block contract', function () {
    $errors = app(BlockRegistry::class)->validate([
        ['type' => 'hero', 'data' => ['headline' => '']],
        ['type' => 'stats', 'data' => ['items' => [['value' => '10', 'label' => 'x']], 'attested' => false]],
        ['type' => 'video', 'data' => ['provider' => 'youtube', 'source' => 'https://example.com/video']],
    ], 'page');

    expect($errors->has('blocks.0'))->toBeTrue()
        ->and($errors->has('blocks.1'))->toBeTrue()
        ->and($errors->has('blocks.2'))->toBeTrue();
});

it('accepts a valid block tree including display settings', function () {
    $errors = app(BlockRegistry::class)->validate([
        ['type' => 'hero', 'data' => ['headline' => 'Technology that moves business forward.', 'is_enabled' => true, 'theme' => 'dark', 'anchor' => 'top']],
        ['type' => 'service_grid', 'data' => ['mode' => 'category', 'service_category_id' => 1, 'limit' => 6]],
        ['type' => 'cta', 'data' => ['cta_id' => 3]],
    ], 'page');

    expect($errors->isEmpty())->toBeTrue();
});

it('rejects malformed entries and invalid themes', function () {
    $errors = app(BlockRegistry::class)->validate(['not-a-block', ['type' => 'rich_text', 'data' => ['body' => 'x', 'theme' => 'neon']]], 'page');

    expect($errors->first('blocks.0'))->toContain('malformed')
        ->and($errors->first('blocks.1'))->toContain('theme');
});

it('works as a validation rule', function () {
    $validator = Validator::make(['blocks' => [['type' => 'nope', 'data' => []]]], ['blocks' => [new ValidBlocks('page')]]);

    expect($validator->fails())->toBeTrue();
});

it('exposes the same display settings on every block', function () {
    foreach (app(BlockRegistry::class)->all() as $block) {
        expect($block)->toBeInstanceOf(Block::class)
            ->and(array_keys($block->allRules()))->toContain('is_enabled', 'theme', 'anchor');
    }
});
