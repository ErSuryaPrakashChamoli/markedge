<?php

namespace App\Search;

use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use App\Seo\IndexabilityResolver;
use App\Services\Cms\PublicUrl;
use Illuminate\Database\Eloquent\Model;

/**
 * Turns an entity into a SearchDocument, or null when it must not be discoverable.
 * Eligibility is the Phase 6 discoverability decision (published, not noindex, not canonicalized
 * elsewhere) plus content-type membership; there is no separate "is searchable" flag.
 */
class SearchDocumentBuilder
{
    public function __construct(
        private readonly IndexabilityResolver $indexability,
        private readonly PublicUrl $urls,
    ) {}

    public function forEntity(Model $entity): ?SearchDocument
    {
        $type = SearchTypes::keyFor($entity);

        if ($type === null || (method_exists($entity, 'trashed') && $entity->trashed())) {
            return null;
        }

        if (! $this->indexability->forEntity($entity->loadMissing('seo'))->discoverable) {
            return null;
        }

        $path = $this->urls->pathFor($entity);

        if ($path === null) {
            return null;
        }

        [$categorySlug, $categoryLabel] = $this->category($entity);

        return new SearchDocument(
            type: $type,
            id: (int) $entity->getKey(),
            title: (string) ($entity->title ?? $entity->name),
            summary: $this->text($entity->excerpt ?? $entity->short_description ?? $entity->tagline ?? null, 300),
            body: $this->text($this->bodyFor($entity), 20000),
            path: $path,
            categorySlug: $categorySlug,
            categoryLabel: $categoryLabel,
            keywords: $this->keywordsFor($entity),
            weight: SearchTypes::weight($type) + (($entity->is_featured ?? false) ? 5 : 0),
            publishedAt: $entity->published_at ?? null,
        );
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected function category(Model $entity): array
    {
        if ($entity instanceof Service) {
            $entity->loadMissing('category');

            return [$entity->category?->slug, $entity->category?->name];
        }

        if ($entity instanceof Article) {
            $entity->loadMissing('category');

            return [$entity->category?->slug, $entity->category?->name];
        }

        if ($entity instanceof ServiceCategory) {
            return [$entity->slug, $entity->name];
        }

        return [null, null];
    }

    protected function bodyFor(Model $entity): string
    {
        $parts = match (true) {
            $entity instanceof Service => [$entity->overview, ...$this->repeaterText($entity->benefits), ...$this->repeaterText($entity->features), ...$this->repeaterText($entity->process), ...(array) ($entity->deliverables ?? [])],
            $entity instanceof ServiceCategory => [$entity->description, $entity->tagline],
            $entity instanceof Product => [$entity->long_description, ...$this->repeaterText($entity->benefits), ...$this->repeaterText($entity->use_cases), ...$entity->features()->pluck('title')->all(), ...$entity->modules()->pluck('name')->all()],
            $entity instanceof Solution => [$entity->problem_statement, $entity->approach, ...$this->repeaterText($entity->outcomes, 'label')],
            $entity instanceof Industry => [$entity->description, ...$this->repeaterText($entity->challenges)],
            $entity instanceof CaseStudy => [$entity->challenge, $entity->solution, $entity->implementation, $entity->results],
            $entity instanceof Article => [$entity->body],
            $entity instanceof Page => $this->blockText($entity->blocks),
            default => [],
        };

        return implode(' ', array_filter(array_map(fn ($part) => is_string($part) ? $part : null, $parts)));
    }

    /**
     * @return array<int, string>
     */
    protected function keywordsFor(Model $entity): array
    {
        $keywords = [];

        if ($entity instanceof Article) {
            $keywords = $entity->tags()->pluck('name')->all();
        }

        if (method_exists($entity, 'technologies')) {
            $keywords = [...$keywords, ...$entity->technologies()->pluck('name')->all()];
        }

        if ($entity instanceof ServiceCategory && filled($entity->pillar_label)) {
            $keywords[] = $entity->pillar_label;
        }

        return array_values(array_unique(array_filter($keywords, 'is_string')));
    }

    /**
     * @param  array<int, mixed>|null  $items
     * @return array<int, string>
     */
    protected function repeaterText(?array $items, string $titleKey = 'title'): array
    {
        $text = [];

        foreach ($items ?? [] as $item) {
            if (is_array($item)) {
                $text[] = (string) ($item[$titleKey] ?? '');
                $text[] = (string) ($item['text'] ?? '');
            }
        }

        return $text;
    }

    /**
     * Only human-readable strings from block data, never ids or settings.
     *
     * @param  array<int, mixed>|null  $blocks
     * @return array<int, string>
     */
    protected function blockText(?array $blocks): array
    {
        $text = [];

        foreach ($blocks ?? [] as $block) {
            $data = is_array($block) ? ($block['data'] ?? []) : [];

            foreach (['headline', 'heading', 'subheading', 'intro', 'body', 'caption'] as $key) {
                if (is_string($data[$key] ?? null)) {
                    $text[] = $data[$key];
                }
            }

            foreach (['items', 'steps', 'entries', 'bullets'] as $listKey) {
                foreach ((array) ($data[$listKey] ?? []) as $item) {
                    if (is_string($item)) {
                        $text[] = $item;
                    } elseif (is_array($item)) {
                        $text[] = (string) ($item['title'] ?? '');
                        $text[] = (string) ($item['text'] ?? '');
                    }
                }
            }
        }

        return $text;
    }

    protected function text(?string $html, int $limit): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

        if ($text === '' || str_contains($text, '[PLACEHOLDER')) {
            return null;
        }

        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) : $text;
    }
}
