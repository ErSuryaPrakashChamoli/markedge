<?php

use App\Models\Article;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Seo\Diagnostics\SeoAudit;
use App\Seo\Diagnostics\SeoCheck;

function checkFor(array $checks, string $key): SeoCheck
{
    return collect($checks)->firstWhere('key', $key) ?? throw new RuntimeException("No check {$key}");
}

it('reports factual checks without a score', function () {
    $service = Service::factory()->published()->create(['slug' => 'seo']);
    SeoMeta::factory()->for($service, 'seoable')->create(['title' => str_repeat('Long ', 30), 'description' => str_repeat('Words ', 60)]);

    $checks = app(SeoAudit::class)->forEntity($service);

    expect(collect($checks)->pluck('level')->unique()->values()->all())->toEqualCanonicalizing(['required', 'recommended', 'informational'])
        ->and(checkFor($checks, 'canonical'))->status->toBe(SeoCheck::PASS)->level->toBe(SeoCheck::REQUIRED)
        ->and(checkFor($checks, 'title')->status)->toBe(SeoCheck::PASS)
        ->and(checkFor($checks, 'title_length')->status)->toBe(SeoCheck::NOTE)
        ->and(checkFor($checks, 'description_length')->status)->toBe(SeoCheck::NOTE)
        ->and(checkFor($checks, 'sitemap')->status)->toBe(SeoCheck::PASS)
        ->and(checkFor($checks, 'schema')->detail)->toContain('Service')
        ->and(collect($checks)->pluck('label')->join(' '))->not->toContain('score');
});

it('flags fallbacks and missing recommended data', function () {
    $article = Article::factory()->create(['author_id' => null, 'excerpt' => null]);

    $checks = app(SeoAudit::class)->forEntity($article);

    expect(checkFor($checks, 'status')->status)->toBe(SeoCheck::NOTE)
        ->and(checkFor($checks, 'title')->status)->toBe(SeoCheck::WARN)
        ->and(checkFor($checks, 'article_author')->status)->toBe(SeoCheck::WARN)
        ->and(checkFor($checks, 'canonical')->status)->toBe(SeoCheck::NOTE);
});
