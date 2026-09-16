<?php

namespace App\Services\Cms;

use App\Models\ArticleCategory;
use App\Models\Industry;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Product;
use App\Models\ServiceCategory;
use App\Models\Solution;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Turns a menu row and its items into a tree of MenuNode objects, resolving entity
 * links, dropping unpublished targets and appending automatic children
 * (architecture §4.2). The tree is cached by content version.
 */
class MenuBuilder
{
    public function __construct(
        private readonly ContentVersion $version,
        private readonly PublicUrl $urls,
    ) {}

    /**
     * @return array<int, MenuNode>
     */
    public function build(string $menuKey): array
    {
        $cached = Cache::remember(
            $this->version->key("menu:{$menuKey}"),
            now()->addDay(),
            fn (): array => array_map(fn (MenuNode $node): array => $node->toArray(), $this->buildFresh($menuKey)),
        );

        return array_map(fn (array $node): MenuNode => MenuNode::fromArray($node), $cached);
    }

    /**
     * Tree with active states for the given request path.
     *
     * @return array<int, MenuNode>
     */
    public function forPath(string $menuKey, string $path): array
    {
        return $this->applyActive($this->build($menuKey), $this->normalisePath($path));
    }

    /**
     * @return array<int, MenuNode>
     */
    protected function buildFresh(string $menuKey): array
    {
        $menu = Menu::query()->where('key', $menuKey)->first();

        if ($menu === null) {
            return [];
        }

        $items = $menu->items()->visible()->with('linkable')->get();

        return $this->nodesFor($items, null);
    }

    /**
     * @param  Collection<int, MenuItem>  $items
     * @return array<int, MenuNode>
     */
    protected function nodesFor(Collection $items, ?int $parentId): array
    {
        $nodes = [];

        foreach ($items->where('parent_id', $parentId) as $item) {
            $node = $this->nodeFor($item, $items);

            if ($node !== null) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * @param  Collection<int, MenuItem>  $items
     */
    protected function nodeFor(MenuItem $item, Collection $items): ?MenuNode
    {
        $url = $item->url;
        $label = $item->label;

        if ($item->linkable_type !== null) {
            $target = $item->linkable;

            if ($target === null || ! $this->urls->isPubliclyVisible($target)) {
                return null;
            }

            $url = $this->urls->pathFor($target) ?? $url;
            $label = $label ?: ($target->name ?? $target->title ?? $label);
        }

        $children = array_merge(
            $this->autoChildren($item),
            $this->nodesFor($items, $item->id),
        );

        return new MenuNode(
            label: $label,
            url: $item->type->value === 'heading' ? null : $url,
            children: $children,
            description: $item->description,
            icon: $item->icon,
            badge: $item->badge,
            openInNewTab: $item->open_in_new_tab,
            isHeading: $item->type->value === 'heading',
            settings: $item->settings ?? [],
        );
    }

    /**
     * @return array<int, MenuNode>
     */
    protected function autoChildren(MenuItem $item): array
    {
        $mode = $item->settings['auto_children'] ?? null;

        if ($mode === null) {
            return [];
        }

        $models = match ($mode) {
            'service_category' => $item->linkable instanceof ServiceCategory
                ? $item->linkable->services()->published()->ordered()->get()
                : collect(),
            'products' => Product::query()->publiclyVisible()->ordered()->get(),
            'solutions' => Solution::query()->published()->ordered()->get(),
            'industries' => Industry::query()->published()->ordered()->get(),
            'article_categories' => ArticleCategory::query()->visible()->ordered()->get(),
            default => collect(),
        };

        return $models->map(fn ($model): MenuNode => new MenuNode(
            label: $model->name,
            url: $this->urls->pathFor($model),
            description: $model->tagline ?? null,
            badge: $model instanceof Product && $model->isComingSoon() ? 'Coming soon' : null,
        ))->all();
    }

    /**
     * @param  array<int, MenuNode>  $nodes
     * @return array<int, MenuNode>
     */
    protected function applyActive(array $nodes, string $path): array
    {
        return array_map(function (MenuNode $node) use ($path): MenuNode {
            $children = $this->applyActive($node->children, $path);
            $childActive = array_any($children, fn (MenuNode $child): bool => $child->isActive);

            return $node->withActive($childActive || $this->matches($node->url, $path), $children);
        }, $nodes);
    }

    protected function matches(?string $url, string $path): bool
    {
        if ($url === null || str_starts_with($url, 'http')) {
            return false;
        }

        $url = $this->normalisePath($url);

        return $url === $path || ($url !== '/' && str_starts_with($path, $url.'/'));
    }

    protected function normalisePath(string $path): string
    {
        $path = '/'.ltrim(strtok($path, '?') ?: '', '/');

        return $path === '/' ? $path : rtrim($path, '/');
    }
}
