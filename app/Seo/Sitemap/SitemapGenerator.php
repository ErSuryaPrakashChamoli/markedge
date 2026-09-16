<?php

namespace App\Seo\Sitemap;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Author;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use App\Seo\IndexabilityResolver;
use App\Services\Cms\ContentVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the sitemap from the central indexability decision. Records stream in chunks,
 * entries are deduplicated by canonical URL, and lastmod comes from real timestamps only.
 *
 * @phpstan-type Entry array{loc: string, lastmod: ?string, changefreq: ?string, priority: ?string}
 */
class SitemapGenerator
{
    public function __construct(
        private readonly IndexabilityResolver $indexability,
        private readonly ContentVersion $version,
    ) {}

    public function xml(): string
    {
        return Cache::remember(
            $this->version->key('sitemap:xml'),
            now()->addMinutes((int) config('markedge.sitemap.cache_minutes', 1440)),
            fn (): string => $this->render($this->entries()),
        );
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string, changefreq: ?string, priority: ?string}>
     */
    public function entries(): array
    {
        if (! config('markedge.seo.indexable')) {
            return [];
        }

        $entries = [];
        $add = function (string $loc, ?Carbon $lastmod, ?string $changefreq = null, ?string $priority = null) use (&$entries): void {
            if (! isset($entries[$loc])) {
                $entries[$loc] = ['loc' => $loc, 'lastmod' => $lastmod?->toAtomString(), 'changefreq' => $changefreq, 'priority' => $priority];
            }
        };

        $this->addStaticEntries($add);

        foreach ($this->sources() as $query) {
            $query->with('seo')->chunkById((int) config('markedge.sitemap.chunk', 500), function ($records) use ($add): void {
                foreach ($records as $record) {
                    $decision = $this->indexability->forEntity($record);

                    if ($decision->inSitemap && $decision->canonical) {
                        $seo = $this->indexability->seoFor($record);
                        $add($decision->canonical, $record->updated_at, $seo?->sitemap_changefreq, $seo?->sitemap_priority !== null ? number_format((float) $seo->sitemap_priority, 1) : null);
                    }
                }
            });
        }

        return array_values($entries);
    }

    /**
     * Home and listing pages. lastmod reflects the newest published record of each section.
     */
    protected function addStaticEntries(\Closure $add): void
    {
        $home = Page::query()->published()->where('slug', Page::HOME_SLUG)->first();
        $listing = $this->indexability->forListing('/');

        if ($home !== null) {
            $homeDecision = $this->indexability->forEntity($home);

            if ($homeDecision->inSitemap) {
                $add($homeDecision->canonical, $home->updated_at);
            }
        } elseif ($listing->inSitemap) {
            $add($listing->canonical, null);
        }

        $sections = [
            '/services' => max(Service::query()->published()->max('updated_at'), ServiceCategory::query()->published()->max('updated_at')),
            '/products' => Product::query()->publiclyVisible()->max('updated_at'),
            '/solutions' => Solution::query()->published()->max('updated_at'),
            '/industries' => Industry::query()->published()->max('updated_at'),
            '/case-studies' => CaseStudy::query()->published()->max('updated_at'),
            '/insights' => Article::query()->published()->max('updated_at'),
        ];

        foreach ($sections as $path => $lastmod) {
            $decision = $this->indexability->forListing($path);

            if ($decision->inSitemap) {
                $add($decision->canonical, $lastmod ? Carbon::parse($lastmod) : null);
            }
        }
    }

    /**
     * @return array<int, Builder<Model>>
     */
    protected function sources(): array
    {
        return [
            Page::query()->published()->where('slug', '!=', Page::HOME_SLUG),
            ServiceCategory::query()->published(),
            Service::query()->published(),
            Product::query()->publiclyVisible(),
            Solution::query()->published(),
            Industry::query()->published(),
            CaseStudy::query()->published(),
            Article::query()->published(),
            ArticleCategory::query()->visible()->whereHas('articles', fn (Builder $q) => $q->published()),
            Author::query()->visible()->whereHas('articles', fn (Builder $q) => $q->published()),
            LandingPage::query()->published(),
        ];
    }

    /**
     * @param  array<int, array{loc: string, lastmod: ?string, changefreq: ?string, priority: ?string}>  $entries
     */
    public function render(array $entries): string
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($entries as $entry) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.htmlspecialchars($entry['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>';

            if ($entry['lastmod']) {
                $lines[] = '    <lastmod>'.htmlspecialchars($entry['lastmod'], ENT_XML1, 'UTF-8').'</lastmod>';
            }

            if ($entry['changefreq'] && in_array($entry['changefreq'], ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'], true)) {
                $lines[] = '    <changefreq>'.$entry['changefreq'].'</changefreq>';
            }

            if ($entry['priority'] !== null && is_numeric($entry['priority'])) {
                $lines[] = '    <priority>'.$entry['priority'].'</priority>';
            }

            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }
}
