<?php

use App\Models\Article;
use App\Models\Faq;
use App\Models\Industry;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use App\Services\Cms\Publisher;
use Database\Seeders\Phase12ContentSeeder;

function seedCatalogueStructure(): void
{
    foreach (['RolesAndPermissionsSeeder', 'SettingSeeder', 'CtaSeeder', 'FormSeeder', 'ServiceCatalogueSeeder', 'ProductSeeder', 'SolutionSeeder', 'IndustrySeeder', 'ArticleCategorySeeder', 'PageSeeder'] as $seeder) {
        if (class_exists($class = "Database\\Seeders\\{$seeder}")) {
            test()->seed($class);
        }
    }

    test()->seed(Phase12ContentSeeder::class);
}

it('fills every commercial record with complete, placeholder-free copy', function () {
    seedCatalogueStructure();

    expect(Service::count())->toBe(24);

    Service::query()->with(['faqs', 'seo'])->get()->each(function (Service $service): void {
        expect($service->tagline)->not->toBeEmpty("{$service->slug} tagline")
            ->and($service->short_description)->not->toBeEmpty("{$service->slug} short description")
            ->and(strlen(strip_tags((string) $service->overview)))->toBeGreaterThan(300, "{$service->slug} overview")
            ->and(count($service->benefits ?? []))->toBeGreaterThanOrEqual(2)
            ->and(count($service->process ?? []))->toBeGreaterThanOrEqual(3)
            ->and(count($service->deliverables ?? []))->toBeGreaterThanOrEqual(3)
            ->and($service->faqs->count())->toBeGreaterThanOrEqual(2, "{$service->slug} faqs")
            ->and($service->seo?->description)->not->toBeEmpty("{$service->slug} seo description");
    });

    ServiceCategory::query()->get()->each(fn (ServiceCategory $c) => expect(strlen(strip_tags((string) $c->description)))->toBeGreaterThan(200));
    Solution::query()->get()->each(fn (Solution $s) => expect($s->problem_statement)->not->toBeEmpty()->and(count($s->outcomes ?? []))->toBeGreaterThanOrEqual(2));
    Industry::query()->where('is_featured', true)->get()->each(fn (Industry $i) => expect(strlen(strip_tags((string) $i->description)))->toBeGreaterThan(300, $i->slug)->and(count($i->challenges ?? []))->toBeGreaterThanOrEqual(3));
    Product::query()->get()->each(fn (Product $p) => expect($p->long_description)->not->toBeEmpty()->and($p->demo_form_id)->not->toBeNull());

    $text = collect([Service::query()->get(['overview', 'short_description', 'tagline']), Solution::query()->get(['problem_statement', 'approach']), Industry::query()->get(['description']), Product::query()->get(['long_description']), Page::query()->whereIn('slug', ['home', 'about', 'contact', 'request-quote', 'request-consultation', 'request-demo', 'request-it-assessment', 'request-digital-growth-audit'])->get(['blocks', 'excerpt'])])
        ->flatten(1)->map(fn ($m) => json_encode($m->toArray()))->join(' ');

    expect($text)->not->toContain('[PLACEHOLDER')->not->toMatch('/\b(guaranteed rankings|#1|clients worldwide|award-winning|ISO \d+|years of experience)\b/i');
});

it('is idempotent and never changes publication status', function () {
    seedCatalogueStructure();
    $before = [Service::count(), Faq::count(), SeoMeta::count(), Article::count(), Service::query()->pluck('status', 'slug')->map->value->all(), Page::query()->pluck('status', 'slug')->map->value->all()];

    $this->seed(Phase12ContentSeeder::class);

    expect([Service::count(), Faq::count(), SeoMeta::count(), Article::count(), Service::query()->pluck('status', 'slug')->map->value->all(), Page::query()->pluck('status', 'slug')->map->value->all()])->toBe($before)
        ->and(Article::query()->pluck('status')->map->value->unique()->all())->toBe(['draft'])
        ->and(Article::query()->whereNotNull('author_id')->count())->toBe(0);
});

it('renders a published service with FAQ schema, description, internal links and a CTA that resolves', function () {
    seedCatalogueStructure();
    $service = Service::query()->where('slug', 'seo')->firstOrFail();
    $service->category->update(['status' => 'published', 'published_at' => now()]);
    app(Publisher::class)->publish($service);
    Solution::query()->where('slug', 'digital-growth')->update(['status' => 'published', 'published_at' => now()]);

    $html = $this->get('/services/seo')->assertOk()->getContent();
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
    $types = collect(json_decode(html_entity_decode($m[1]), true)['@graph'])->pluck('@type')->all();

    expect($html)->toContain('name="description" content="Technical SEO, keyword and intent research')
        ->toContain('Can you guarantee first-page rankings?')
        ->toContain('href="/solutions/digital-growth"')
        ->toContain('/go/')
        ->and($types)->toContain('Service')->toContain('FAQPage')->toContain('BreadcrumbList')
        ->and($html)->not->toContain('AggregateRating')->not->toContain('"Review"');

    $go = preg_match('#href="([^"]*/go/[a-z0-9-]+[^"]*)"#', $html, $link) ? html_entity_decode($link[1]) : null;
    $this->get($go)->assertStatus(302);
});

it('renders the conversion pages with their forms, consent and post-submission explanation', function () {
    seedCatalogueStructure();

    foreach (['contact', 'request-quote', 'request-consultation', 'request-demo', 'request-it-assessment', 'request-digital-growth-audit'] as $slug) {
        $page = Page::query()->where('slug', $slug)->firstOrFail();
        app(Publisher::class)->publish($page);
        $html = $this->get('/'.$slug)->assertOk()->getContent();

        expect($html)->toContain('wire:submit="submit"', $slug)
            ->toContain('accept the privacy policy', $slug)
            ->toMatch('/What (happens|a consultation covers|to expect|an IT assessment|the audit covers)|How quoting works/', $slug)
            ->toContain('name="robots" content="index, follow"', $slug);
    }
});

it('seeds the full product platform for both products idempotently', function () {
    seedCatalogueStructure();

    foreach (['lead-management-system', 'recruitment-management-system'] as $slug) {
        $product = Product::query()->where('slug', $slug)->with(['modules.features.capabilities', 'documents'])->firstOrFail();

        expect($product->modules->count())->toBeGreaterThanOrEqual(4, "{$slug} modules")
            ->and($product->modules->flatMap->features->count())->toBeGreaterThanOrEqual(8, "{$slug} features")
            ->and($product->modules->flatMap->features->flatMap->capabilities->count())->toBeGreaterThanOrEqual(12, "{$slug} capabilities")
            ->and(count($product->deployment))->toBe(2)
            ->and(count($product->security))->toBeGreaterThanOrEqual(3)
            ->and($product->documents->count())->toBe(3)
            ->and($product->documents->every(fn ($doc) => $doc->isPublished()))->toBeTrue()
            ->and(strlen(strip_tags((string) $product->long_description)))->toBeGreaterThan(500);
    }

    // Re-running updates in place instead of duplicating.
    test()->seed(Phase12ContentSeeder::class);
    $lms = Product::query()->where('slug', 'lead-management-system')->firstOrFail();
    expect($lms->modules()->count())->toBe(4)->and($lms->documents()->count())->toBe(3)->and($lms->features()->count())->toBe(11);
});
