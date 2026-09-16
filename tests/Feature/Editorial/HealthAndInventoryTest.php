<?php

use App\Editorial\ContentHealthAuditor;
use App\Editorial\ContentHealthReport;
use App\Editorial\ContentInventoryQuery;
use App\Enums\PublishStatus;
use App\Filament\Pages\ContentHealth;
use App\Filament\Pages\ContentInventory;
use App\Filament\Pages\ReviewQueue;
use App\Filament\Widgets\MyEditorialQueue;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Author;
use App\Models\Menu;
use App\Models\Page;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\ServiceCategory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
});

it('diagnoses missing metadata, invalid blocks, broken links, noindex and repairs', function () {
    $this->actingAs(adminUser());
    $article = Article::factory()->create(['excerpt' => null, 'author_id' => null, 'article_category_id' => null, 'body' => '<p><a href="/services/does-not-exist">x</a></p>']);
    SeoMeta::factory()->for($article, 'seoable')->create(['robots_index' => false, 'description' => null]);
    $page = Page::factory()->create(['blocks' => [['type' => 'not_a_block', 'data' => []]]]);

    $keys = collect(app(ContentHealthAuditor::class)->check($article))->pluck('key');
    expect($keys)->toContain('missing_description', 'missing_image', 'missing_author', 'missing_category', 'broken_link', 'noindex', 'no_owner');
    expect(collect(app(ContentHealthAuditor::class)->check($page))->pluck('key'))->toContain('invalid_block');

    $article->update(['excerpt' => 'Now summarised', 'author_id' => Author::factory()->create()->id, 'article_category_id' => ArticleCategory::factory()->create()->id, 'body' => '<p>clean</p>', 'owner_id' => auth()->id()]);
    $article->seo->update(['robots_index' => true]);
    $repaired = collect(app(ContentHealthAuditor::class)->check($article->fresh()))->pluck('key');
    expect($repaired)->not->toContain('missing_description', 'missing_author', 'missing_category', 'broken_link', 'noindex', 'no_owner');
});

it('aggregates health counts in SQL and detects orphaned pages', function () {
    Page::factory()->published()->create(['slug' => 'orphan-page', 'title' => 'Orphan']);
    $linked = Page::factory()->published()->create(['slug' => 'linked-page']);
    Menu::factory()->create()->items()->create(['label' => 'Linked', 'type' => 'entity', 'linkable_type' => 'page', 'linkable_id' => $linked->id]);
    Article::factory()->create(['status' => PublishStatus::Review]);
    Service::factory()->create(['status' => PublishStatus::Scheduled, 'published_at' => now()->addDay()]);
    Service::factory()->create(['unpublish_at' => now()->subDay()]);

    DB::enableQueryLog();
    $counts = app(ContentHealthReport::class)->counts();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($counts['awaiting_review'])->toBe(1)->and($counts['scheduled'])->toBe(1)->and($counts['expired'])->toBe(1)
        ->and($counts['orphaned_pages'])->toBe(1)
        ->and(app(ContentHealthReport::class)->list('orphaned_pages')->pluck('slug')->all())->toBe(['orphan-page'])
        ->and($queries)->toBeLessThan(40);

    $this->actingAs(adminUser('Website Manager'));
    Livewire::test(ContentHealth::class)->assertOk()->assertSee('Orphaned pages')->set('list', 'orphaned_pages')->assertSee('Orphan');
});

it('lists the inventory across types with filters and bounded pagination', function () {
    $owner = adminUser('Content Manager');
    $category = ServiceCategory::factory()->published()->create(['slug' => 'technology']);
    Service::factory()->for($category, 'category')->published()->count(3)->create();
    Article::factory()->create(['title' => 'Owned draft', 'owner_id' => $owner->id, 'status' => PublishStatus::Review, 'submitted_at' => now()]);
    $noindex = Article::factory()->published()->create(['title' => 'Hidden article']);
    SeoMeta::factory()->for($noindex, 'seoable')->create(['robots_index' => false]);

    $query = app(ContentInventoryQuery::class);

    expect($query->paginate([])->total())->toBe(6)
        ->and($query->paginate(['type' => 'service'])->total())->toBe(3)
        ->and($query->paginate(['category' => 'technology'])->total())->toBe(3)
        ->and($query->paginate(['owner' => $owner->id])->total())->toBe(1)
        ->and($query->paginate(['status' => ['review']])->total())->toBe(1)
        ->and($query->paginate(['indexability' => 'noindex'])->total())->toBe(1)
        ->and($query->paginate(['indexability' => 'indexable'])->total())->toBe(4)
        ->and($query->paginate(['search' => 'Hidden'])->total())->toBe(1)
        ->and($query->paginate([], ['article'])->total())->toBe(2)
        ->and($query->paginate([], null, 500)->perPage())->toBe(100);

    $this->actingAs(adminUser('Content Manager'));
    Livewire::test(ContentInventory::class)->assertOk()->assertSee('Owned draft')->assertSee('Hidden article')->assertDontSee('/services/');
    Livewire::test(ReviewQueue::class)->assertOk()->assertSee('Owned draft')->assertDontSee('Hidden article');

    $this->actingAs(adminUser('Sales'));
    expect(ContentInventory::canAccess())->toBeFalse();
});

it('shows each editor their own queue', function () {
    $me = adminUser('Content Manager');
    Article::factory()->count(2)->create(['owner_id' => $me->id]);
    Article::factory()->create(['reviewer_id' => $me->id, 'status' => PublishStatus::Review]);
    Article::factory()->create(['owner_id' => adminUser('Editor')->id]);

    $this->actingAs($me);
    Livewire::test(MyEditorialQueue::class)->assertSee('Assigned to me')->assertSee('2')->assertSee('Awaiting my review');
});
