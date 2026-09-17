<?php

namespace App\Cms\Blocks;

use App\Enums\TechnologyCategory;
use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Client;
use App\Models\Cta;
use App\Models\Faq;
use App\Models\Form;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use App\Models\Technology;
use App\Models\Testimonial;
use App\Services\Cms\CtaResolver;
use App\Services\Cms\RelatedContentResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Storage;

/**
 * Resolves stored block data into render-ready data: referenced records become published
 * models, ids that no longer exist are dropped, and a block with nothing to show returns
 * null so the section disappears (architecture §6.2, §12).
 */
class BlockHydrator
{
    public function __construct(
        private readonly CtaResolver $ctas,
        private readonly RelatedContentResolver $related,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function hydrate(Block $block, array $data, ?Model $host): ?array
    {
        $entityName = $host?->name ?? $host?->title ?? null;

        return match ($block->key()) {
            'hero' => array_replace($data, [
                'primaryCta' => $this->ctaLink($data['primary_cta_id'] ?? null, $entityName),
                'secondaryCta' => $this->ctaLink($data['secondary_cta_id'] ?? null, $entityName)
                    ?? (filled($data['secondary_label'] ?? null) && filled($data['secondary_url'] ?? null)
                        ? ['label' => $data['secondary_label'], 'href' => $data['secondary_url'], 'external' => str_starts_with($data['secondary_url'], 'http')]
                        : null),
                'imageUrl' => $this->imageUrl($data['image'] ?? null),
                'slides' => collect($data['slides'] ?? [])
                    ->filter(fn ($slide) => is_array($slide) && filled($slide['image'] ?? null))
                    ->map(fn (array $slide) => $slide + [
                        'imageUrl' => $this->imageUrl($slide['image']),
                        'external' => str_starts_with((string) ($slide['button_url'] ?? ''), 'http'),
                    ])->values()->all(),
            ]),
            'capability_intro' => $this->nonEmpty(['categories', 'products'], $data + [
                'categories' => ServiceCategory::query()->published()->ordered()->withCount(['services' => fn ($q) => $q->published()])->get(),
                'products' => ($data['show_products'] ?? true) ? Product::query()->publiclyVisible()->ordered()->get() : new Collection,
            ]),
            'split_content' => $data + ['cta' => $this->ctaLink($data['cta_id'] ?? null, $entityName), 'imageUrl' => $this->imageUrl($data['image'] ?? null)],
            'rich_text' => filled(strip_tags((string) ($data['body'] ?? ''))) ? $data : null,
            'image_content' => ($url = $this->imageUrl($data['image'] ?? null)) ? $data + ['imageUrl' => $url] : null,
            'video' => ($embed = $this->embedUrl($data['provider'] ?? '', $data['source'] ?? '')) ? $data + ['embedUrl' => $embed, 'posterUrl' => $this->imageUrl($data['poster'] ?? null)] : null,
            'feature_grid', 'stats' => filled($data['items'] ?? null) ? $data : null,
            'process' => filled($data['steps'] ?? null) ? $data : null,
            'timeline' => filled($data['entries'] ?? null) ? $data : null,
            'comparison' => filled($data['rows'] ?? null) && filled($data['columns'] ?? null) ? $data : null,
            'service_grid' => $this->serviceGrid($data),
            'product_showcase' => $this->nonEmpty(['products'], $data + ['products' => $this->pick(
                ($data['mode'] ?? 'all_active') === 'ids' ? $data['product_ids'] ?? [] : null,
                Product::query()->publiclyVisible()->ordered()->with('media'),
                $data['limit'] ?? null,
            )]),
            'solution_grid' => $this->nonEmpty(['solutions'], $data + ['solutions' => $this->pick(
                ($data['mode'] ?? 'featured') === 'ids' ? $data['solution_ids'] ?? [] : null,
                Solution::query()->published()->ordered()->when(($data['mode'] ?? 'featured') === 'featured', fn ($q) => $q->featured()),
                $data['limit'] ?? 8,
            )]),
            'industry_grid' => $this->nonEmpty(['industries'], $data + ['industries' => $this->pick(
                ($data['mode'] ?? 'featured') === 'ids' ? $data['industry_ids'] ?? [] : null,
                Industry::query()->published()->ordered()->when(($data['mode'] ?? 'featured') === 'featured', fn ($q) => $q->featured()),
                $data['limit'] ?? 12,
            )]),
            'technology_grid' => $this->technologyGrid($data),
            'logo_cloud' => $this->nonEmpty(['clients'], $data + ['clients' => $this->pick(
                ($data['mode'] ?? 'all_visible') === 'ids' ? $data['client_ids'] ?? [] : null,
                Client::query()->visible()->ordered()->with('media')->when(($data['mode'] ?? 'all_visible') === 'all_visible', fn ($q) => $q->inLogoCloud()),
                null,
            )]),
            'case_study_grid' => $this->nonEmpty(['caseStudies'], $data + ['caseStudies' => $this->pick(
                ($data['mode'] ?? 'latest') === 'ids' ? $data['case_study_ids'] ?? [] : null,
                CaseStudy::query()->published()->with(['client', 'industry', 'media'])->latest('published_at')->when(($data['mode'] ?? 'latest') === 'featured', fn ($q) => $q->featured()),
                $data['limit'] ?? 3,
            )]),
            'testimonials' => $this->testimonials($data, $host),
            'faq' => $this->faqs($data, $host),
            'cta' => ($cta = $this->cta($data['cta_id'] ?? null)) ? $data + ['cta' => $cta, 'links' => $this->ctas->links($cta, $entityName)] : null,
            'lead_form', 'contact_form' => $this->form($data, $host),
            'article_grid' => $this->nonEmpty(['articles'], $data + ['articles' => $this->pick(
                ($data['mode'] ?? 'latest') === 'ids' ? $data['article_ids'] ?? [] : null,
                Article::query()->published()->with(['author', 'category', 'media'])->latest('published_at')
                    ->when(($data['mode'] ?? 'latest') === 'category' && filled($data['article_category_id'] ?? null), fn ($q) => $q->where('article_category_id', $data['article_category_id'])),
                $data['limit'] ?? 3,
            )]),
            'related_content' => $this->relatedContent($data, $host),
            'related_services' => $this->nonEmpty(['services'], $data + ['services' => ($data['mode'] ?? 'auto') === 'ids'
                ? $this->pick($data['service_ids'] ?? [], Service::query()->published()->ordered()->with('category'), $data['limit'] ?? 4)
                : ($host ? $this->related->services($host, (int) ($data['limit'] ?? 4)) : new Collection)]),
            'related_products' => $this->nonEmpty(['products'], $data + ['products' => ($data['mode'] ?? 'auto') === 'ids'
                ? $this->pick($data['product_ids'] ?? [], Product::query()->publiclyVisible()->ordered()->with('media'), $data['limit'] ?? 3)
                : ($host ? $this->related->products($host, (int) ($data['limit'] ?? 3)) : new Collection)]),
            default => $data,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function serviceGrid(array $data): ?array
    {
        $mode = $data['mode'] ?? 'category';
        $category = null;

        if ($mode === 'category') {
            $category = ServiceCategory::query()->published()->whereKey($data['service_category_id'] ?? 0)->first();
            $services = $category
                ? $category->services()->published()->ordered()->with('media')->limit((int) ($data['limit'] ?? 6))->get()
                : new Collection;
        } else {
            $services = $this->pick($data['service_ids'] ?? [], Service::query()->published()->ordered()->with(['category', 'media']), $data['limit'] ?? null);
        }

        return $this->nonEmpty(['services'], $data + ['services' => $services, 'category' => $category]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function technologyGrid(array $data): ?array
    {
        $mode = $data['mode'] ?? 'all';
        $query = Technology::query()->visible()->ordered()->with('media');

        $technologies = match ($mode) {
            'ids' => $this->pick($data['technology_ids'] ?? [], $query, null),
            'categories' => $query->whereIn('category', $data['category_keys'] ?? [])->get(),
            default => $query->get(),
        };

        if ($technologies->isEmpty()) {
            return null;
        }

        $groups = $technologies->groupBy(fn (Technology $technology) => $technology->category->value)
            ->map(fn (Collection $items, string $key) => ['label' => TechnologyCategory::from($key)->getLabel(), 'items' => $items])
            ->sortBy(fn (array $group, string $key) => array_search($key, array_column(TechnologyCategory::cases(), 'value'), true));

        return $data + ['technologies' => $technologies, 'groups' => $groups];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function testimonials(array $data, ?Model $host): ?array
    {
        $mode = $data['mode'] ?? 'all_visible';
        $query = Testimonial::query()->visible()->ordered()->with(['client', 'media']);

        $testimonials = match (true) {
            $mode === 'ids' => $this->pick($data['testimonial_ids'] ?? [], $query, $data['limit'] ?? null),
            $mode === 'for_host' && $host !== null && method_exists($host, 'testimonials') => $host->testimonials()->visible()->ordered()->with(['client', 'media'])->limit((int) ($data['limit'] ?? 3))->get(),
            $mode === 'for_host' => new Collection,
            default => $query->limit((int) ($data['limit'] ?? 3))->get(),
        };

        return $this->nonEmpty(['testimonials'], $data + ['testimonials' => $testimonials]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function faqs(array $data, ?Model $host): ?array
    {
        $faqs = ($data['mode'] ?? 'host_faqs') === 'ids'
            ? $this->pick($data['faq_ids'] ?? [], Faq::query()->visible()->ordered(), null)
            : ($host !== null && method_exists($host, 'faqs') ? $host->faqs()->visible()->get() : new Collection);

        return $this->nonEmpty(['faqs'], $data + ['faqs' => $faqs]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function form(array $data, ?Model $host): ?array
    {
        $formId = $data['form_id'] ?? null;

        if (blank($formId) && ($host instanceof LandingPage || $host instanceof Page)) {
            $formId = $host->form_id;
        }

        $form = filled($formId) ? Form::query()->active()->whereKey($formId)->with('fields')->first() : null;

        if ($form === null) {
            return null;
        }

        return $data + [
            'form' => $form,
            'context' => array_filter([
                'service_id' => $data['preselect_service_id'] ?? null,
                'product_id' => $data['preselect_product_id'] ?? null,
                'landing_page_id' => $host instanceof LandingPage ? $host->id : null,
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function relatedContent(array $data, ?Model $host): ?array
    {
        if ($host === null) {
            return null;
        }

        $items = $this->related->discover($host, (int) ($data['limit'] ?? 6));

        return $items->isEmpty() ? null : $data + ['items' => $items];
    }

    /**
     * Records for explicit ids (in the admin's order) or the given query.
     *
     * @param  array<int, int|string>|null  $ids
     */
    protected function pick(?array $ids, Builder|Relation $query, int|string|null $limit): Collection
    {
        if ($ids !== null) {
            $ids = array_values(array_filter(array_map('intval', $ids)));

            if ($ids === []) {
                return new Collection;
            }

            $records = $query->whereIn($query->getModel()->getQualifiedKeyName(), $ids)->get();

            return $records->sortBy(fn (Model $record): int => array_search($record->getKey(), $ids, true))->values();
        }

        return $query->when($limit, fn ($q) => $q->limit((int) $limit))->get();
    }

    /**
     * @param  array<int, string>  $keys
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function nonEmpty(array $keys, array $data): ?array
    {
        foreach ($keys as $key) {
            if (($data[$key] ?? null) instanceof Collection && $data[$key]->isNotEmpty()) {
                return $data;
            }
        }

        return null;
    }

    protected function cta(int|string|null $id): ?Cta
    {
        return filled($id) ? Cta::query()->active()->whereKey($id)->first() : null;
    }

    /**
     * @return array{label: string, href: string, external: bool}|null
     */
    protected function ctaLink(int|string|null $id, ?string $entityName): ?array
    {
        $cta = $this->cta($id);

        return $cta ? $this->ctas->links($cta, $entityName)['primary'] : null;
    }

    protected function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : Storage::disk('public')->url($path);
    }

    protected function embedUrl(string $provider, string $source): ?string
    {
        if ($provider === 'youtube' && preg_match('#(?:youtu\.be/|v=|/embed/|/shorts/)([A-Za-z0-9_-]{6,})#', $source, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        if ($provider === 'vimeo' && preg_match('#vimeo\.com/(?:video/)?(\d+)#', $source, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }
}
