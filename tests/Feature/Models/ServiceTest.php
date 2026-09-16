<?php

use App\Models\Faq;
use App\Models\Industry;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Technology;
use Illuminate\Database\QueryException;

it('belongs to a service category and lists in category order', function () {
    $category = ServiceCategory::factory()->create();
    $second = Service::factory()->for($category, 'category')->create(['sort_order' => 2]);
    $first = Service::factory()->for($category, 'category')->create(['sort_order' => 1]);

    expect($category->services->pluck('id')->all())->toBe([$first->id, $second->id]);
});

it('prevents deleting a category that still has services', function () {
    $category = ServiceCategory::factory()->create();
    Service::factory()->for($category, 'category')->create();

    $category->forceDelete();
})->throws(QueryException::class);

it('attaches technologies, faqs and seo through shared concerns', function () {
    $service = Service::factory()->create();
    $technology = Technology::factory()->create();

    $service->technologies()->attach($technology, ['sort_order' => 0]);
    Faq::factory()->for($service, 'faqable')->create();
    SeoMeta::factory()->for($service, 'seoable')->create(['title' => 'Custom SEO title']);

    expect($service->technologies->pluck('id')->all())->toBe([$technology->id])
        ->and($technology->services->pluck('id')->all())->toBe([$service->id])
        ->and($service->faqs)->toHaveCount(1)
        ->and($service->seo->title)->toBe('Custom SEO title');
});

it('relates to industries from both sides', function () {
    $service = Service::factory()->create();
    $industry = Industry::factory()->create();

    $service->industries()->attach($industry, ['sort_order' => 0]);

    expect($industry->services->pluck('id')->all())->toBe([$service->id]);
});

it('stores polymorphic types using morph map aliases', function () {
    $service = Service::factory()->create();
    Faq::factory()->for($service, 'faqable')->create();

    expect(DB::table('faqs')->value('faqable_type'))->toBe('service');
});
