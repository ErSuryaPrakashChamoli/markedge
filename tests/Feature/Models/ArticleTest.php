<?php

use App\Models\Article;
use App\Models\Product;
use App\Models\Service;
use App\Models\Tag;

it('estimates reading time from the body word count on save', function () {
    $body = '<p>'.implode(' ', array_fill(0, 450, 'word')).'</p>';

    $article = Article::factory()->create(['body' => $body]);

    expect($article->reading_time_minutes)->toBe(3);
});

it('never reports less than one minute of reading time', function () {
    $article = Article::factory()->create(['body' => '<p>Short.</p>']);

    expect($article->reading_time_minutes)->toBe(1);
});

it('links to services and products bidirectionally', function () {
    $article = Article::factory()->create();
    $service = Service::factory()->create();
    $product = Product::factory()->create();

    $article->services()->attach($service, ['sort_order' => 0]);
    $article->products()->attach($product, ['sort_order' => 0]);

    expect($article->services->pluck('id')->all())->toBe([$service->id])
        ->and($service->articles->pluck('id')->all())->toBe([$article->id])
        ->and($product->articles->pluck('id')->all())->toBe([$article->id]);
});

it('removes tag associations when the article is force deleted', function () {
    $article = Article::factory()->create();
    $article->tags()->attach(Tag::factory()->create());

    $article->forceDelete();

    expect(DB::table('article_tag')->count())->toBe(0);
});
