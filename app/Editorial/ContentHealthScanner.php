<?php

namespace App\Editorial;

use App\Cms\Blocks\BlockRegistry;
use App\Models\Concerns\HasBlocks;
use App\Models\Redirect;
use App\Services\Cms\ContentVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Bounded scan of block trees and article bodies for invalid blocks and broken internal links.
 * Runs in chunks, keeps at most a few hundred findings, and is cached per content version.
 */
class ContentHealthScanner
{
    public const int MAX_FINDINGS = 200;

    /** @var array<string, string> alias => public path prefix */
    protected const array PREFIXES = [
        'page' => '/', 'service_category' => '/services/', 'service' => '/services/', 'product' => '/products/', 'solution' => '/solutions/',
        'industry' => '/industries/', 'case_study' => '/case-studies/', 'article' => '/insights/', 'landing_page' => '/lp/',
    ];

    /** @var array<int, string> */
    protected const array STATIC_PATHS = ['/', '/services', '/products', '/solutions', '/industries', '/case-studies', '/insights', '/search'];

    /** @var array<int, string> */
    protected const array IGNORED_PREFIXES = ['/insights/category/', '/insights/tag/', '/insights/author/', '/storage/', '/build/', '/go/', '/preview/', '/admin'];

    public function __construct(
        private readonly BlockRegistry $blocks,
        private readonly ContentVersion $version,
    ) {}

    /**
     * @return array{invalid_blocks: array<int, array{type: string, id: int, title: string, message: string}>, broken_links: array<int, array{type: string, id: int, title: string, link: string}>, link_targets: array<int, string>, scanned: int}
     */
    public function results(): array
    {
        return Cache::remember($this->version->key('content-health:scan'), 600, fn (): array => $this->scan());
    }

    public function forget(): void
    {
        Cache::forget($this->version->key('content-health:scan'));
    }

    /**
     * Internal links inside one record that do not resolve to a public path.
     *
     * @return array<int, string>
     */
    public function brokenLinksIn(Model $record): array
    {
        $paths = $this->publicPaths();

        return array_values(array_filter($this->linksIn($record), fn (string $link): bool => ! $this->resolves($link, $paths)));
    }

    /**
     * @return array{invalid_blocks: array<int, array{type: string, id: int, title: string, message: string}>, broken_links: array<int, array{type: string, id: int, title: string, link: string}>, link_targets: array<int, string>, scanned: int}
     */
    protected function scan(): array
    {
        $paths = $this->publicPaths();
        $invalid = [];
        $broken = [];
        $targets = [];
        $scanned = 0;

        foreach (WorkflowModels::all() as $alias => $class) {
            $hasBlocks = in_array(HasBlocks::class, class_uses_recursive($class), true);
            $columns = array_values(array_filter(['id', 'slug', 'status', $hasBlocks ? 'blocks' : null, $alias === 'article' ? 'body' : null, $alias === 'page' || $alias === 'case_study' || $alias === 'article' || $alias === 'landing_page' ? 'title' : 'name']));

            $class::query()->select($columns)->chunkById(200, function ($records) use (&$invalid, &$broken, &$targets, &$scanned, $alias, $paths, $hasBlocks): void {
                foreach ($records as $record) {
                    $scanned++;
                    $title = WorkflowModels::titleOf($record);

                    if ($hasBlocks && count($invalid) < self::MAX_FINDINGS) {
                        $errors = $this->blocks->validate($record->blocks, $alias);

                        if ($errors->isNotEmpty()) {
                            $invalid[] = ['type' => $alias, 'id' => (int) $record->id, 'title' => $title, 'message' => $errors->first()];
                        }
                    }

                    foreach ($this->linksIn($record) as $link) {
                        $targets[$link] = true;

                        if (count($broken) < self::MAX_FINDINGS && ! $this->resolves($link, $paths)) {
                            $broken[] = ['type' => $alias, 'id' => (int) $record->id, 'title' => $title, 'link' => $link];
                        }
                    }
                }
            });
        }

        return ['invalid_blocks' => $invalid, 'broken_links' => $broken, 'link_targets' => array_keys($targets), 'scanned' => $scanned];
    }

    /**
     * Internal hrefs found in blocks and rich text: "/path" values on link-like keys and href attributes.
     *
     * @return array<int, string>
     */
    protected function linksIn(Model $record): array
    {
        $sources = [];

        if (is_array($record->blocks ?? null)) {
            $sources[] = json_encode($record->blocks, JSON_UNESCAPED_SLASHES) ?: '';
        }

        if (is_string($record->body ?? null)) {
            $sources[] = $record->body;
        }

        $links = [];

        foreach ($sources as $source) {
            preg_match_all('/"(?:[a-z_]*url|href|link)":"(\/[^"\s]*)"/i', $source, $keyed);
            preg_match_all('/href=\\\\?"(\/[^"\\\\\s]*)/i', $source, $attrs);

            foreach (array_merge($keyed[1] ?? [], $attrs[1] ?? []) as $link) {
                $path = parse_url(str_replace('\\/', '/', $link), PHP_URL_PATH);

                if (is_string($path) && $path !== '' && ! str_starts_with($path, '//')) {
                    $links[rtrim($path, '/') ?: '/'] = true;
                }
            }
        }

        return array_keys($links);
    }

    /**
     * @param  array<string, true>  $paths
     */
    protected function resolves(string $link, array $paths): bool
    {
        foreach (self::IGNORED_PREFIXES as $prefix) {
            if (str_starts_with($link, $prefix)) {
                return true;
            }
        }

        return isset($paths[$link]);
    }

    /**
     * Every public path that currently answers: published records, section indexes, active redirects.
     *
     * @return array<string, true>
     */
    protected function publicPaths(): array
    {
        $paths = array_fill_keys(self::STATIC_PATHS, true);

        foreach (ContentInventoryQuery::TYPES as $alias => $definition) {
            $slugs = DB::table($definition['table'])->whereNull('deleted_at')->where('status', 'published')->pluck('slug');

            foreach ($slugs as $slug) {
                $paths[$alias === 'page' && $slug === 'home' ? '/' : self::PREFIXES[$alias].$slug] = true;
            }
        }

        foreach (DB::table('products')->whereNull('deleted_at')->whereIn('status', ['active', 'coming_soon'])->pluck('slug') as $slug) {
            $paths[self::PREFIXES['product'].$slug] = true;
        }

        foreach (Redirect::query()->where('is_active', true)->pluck('from_path') as $from) {
            $paths[$from] = true;
        }

        return $paths;
    }
}
