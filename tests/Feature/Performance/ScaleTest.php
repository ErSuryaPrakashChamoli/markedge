<?php

use App\Models\SearchEntry;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Seo\Sitemap\SitemapGenerator;
use Illuminate\Support\Facades\DB;

function seedSearchEntries(int $count): void
{
    $rows = [];
    $now = now()->toDateTimeString();
    $kinds = ['service', 'article', 'product', 'solution'];

    for ($i = 1; $i <= $count; $i++) {
        $rows[] = [
            'searchable_type' => 'article', 'searchable_id' => $i, 'kind' => $kinds[$i % 4], 'category_slug' => 'cat-'.($i % 20),
            'title' => 'Entry '.$i.' '.['cloud', 'software', 'network', 'seo'][$i % 4].' topic',
            'summary' => 'Summary for entry '.$i, 'body_text' => 'Body text mentioning automation and cloud infrastructure number '.$i,
            'keywords' => null, 'weight' => 10, 'url' => '/insights/entry-'.$i, 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ];

        if (count($rows) === 500) {
            DB::table('search_entries')->insert($rows);
            $rows = [];
        }
    }

    if ($rows !== []) {
        DB::table('search_entries')->insert($rows);
    }
}

it('searches large indexes with bounded queries and pagination', function (int $count) {
    seedSearchEntries($count);
    expect(SearchEntry::count())->toBe($count);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $start = hrtime(true);
    $this->get('/search?q=cloud+topic&type=service&page=3')->assertOk()->assertSee('page=4');
    $ms = (hrtime(true) - $start) / 1e6;
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $searchQueries = array_values(array_filter($queries, fn (array $q) => str_contains($q['query'], 'search_entries')));

    expect(count($searchQueries))->toBeLessThanOrEqual(2)
        ->and(collect($searchQueries)->every(fn (array $q) => str_contains($q['query'], 'count(*)') || str_contains($q['query'], 'limit 10')))->toBeTrue()
        ->and(count($queries))->toBeLessThanOrEqual(25);

    fwrite(STDERR, "\nSearch over {$count} entries (SQLite LIKE fallback, local): ".round($ms).' ms, '.count($queries)." queries\n");
})->with([10000, 50000, 100000]);

it('generates the sitemap in chunks without loading everything into memory', function () {
    $category = ServiceCategory::factory()->published()->create();
    $now = now()->toDateTimeString();
    $rows = [];

    for ($i = 1; $i <= 20000; $i++) {
        $rows[] = ['service_category_id' => $category->id, 'name' => 'Service '.$i, 'slug' => 'service-'.$i, 'status' => 'published', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now, 'sort_order' => $i];

        if (count($rows) === 500) {
            DB::table('services')->insert($rows);
            $rows = [];
        }
    }

    expect(Service::count())->toBe(20000);

    $before = memory_get_usage();
    $xml = app(SitemapGenerator::class)->xml();
    $peak = (memory_get_peak_usage() - $before) / 1048576;

    expect(substr_count($xml, '<url>'))->toBe(20008)
        ->and(strpos($xml, '/services/service-1<'))->toBeLessThan(strpos($xml, '/services/service-2<'))
        ->and($peak)->toBeLessThan(96);

    fwrite(STDERR, "\nSitemap for 20,002 URLs: ".round(strlen($xml) / 1024).' kB, peak memory delta '.round($peak, 1)." MB\n");
});
