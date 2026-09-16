<?php

namespace App\Seo;

use App\Models\Faq;
use App\Seo\Schema\SchemaContext;
use App\Seo\Schema\SchemaGraphBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * The one entry point templates and controllers use: metadata, indexability and structured data
 * resolved together so they always agree.
 */
class SeoEngine
{
    public function __construct(
        private readonly MetaResolver $meta,
        private readonly IndexabilityResolver $indexability,
        private readonly SchemaGraphBuilder $schema,
    ) {}

    /**
     * @param  array<int, array{label: string, url: ?string}>  $breadcrumbs
     * @param  Collection<int, Faq>|null  $faqs  FAQs rendered on the page
     */
    public function forEntity(Model $entity, array $breadcrumbs = [], ?Collection $faqs = null, bool $preview = false): PageMeta
    {
        $meta = $this->meta->forEntity($entity, $preview);
        $decision = $meta->indexability ?? $this->indexability->forEntity($entity, $preview);

        if ($preview || ! $decision->schemaEligible || $decision->canonical === null) {
            return $meta;
        }

        $graph = $this->schema->build(new SchemaContext(
            entity: $entity,
            meta: $meta,
            indexability: $decision,
            url: $decision->canonical,
            siteUrl: $this->indexability->siteUrl(),
            breadcrumbs: $breadcrumbs,
            faqs: $faqs,
            pageType: 'WebPage',
        ));

        return $meta->with(['schema' => $graph]);
    }

    /**
     * @param  array<int, array{label: string, url: ?string}>  $breadcrumbs
     */
    public function forListing(?string $title, ?string $description, string $path, bool $indexable = true, array $breadcrumbs = []): PageMeta
    {
        $meta = $this->meta->forListing($title, $description, $path, $indexable);
        $decision = $meta->indexability;

        if ($decision === null || ! $decision->schemaEligible || $decision->canonical === null) {
            return $meta;
        }

        $graph = $this->schema->build(new SchemaContext(
            entity: null,
            meta: $meta,
            indexability: $decision,
            url: $decision->canonical,
            siteUrl: $this->indexability->siteUrl(),
            breadcrumbs: $breadcrumbs,
            pageType: $path === '/' ? 'WebPage' : 'CollectionPage',
        ));

        return $meta->with(['schema' => $graph]);
    }

    public function withSuffix(?string $title): string
    {
        return $this->meta->withSuffix($title);
    }
}
