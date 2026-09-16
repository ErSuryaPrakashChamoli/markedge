<?php

namespace App\Seo\Schema;

use App\Models\Faq;
use App\Seo\Indexability;
use App\Seo\PageMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Everything a schema builder may read. Builders never query beyond this and the settings.
 *
 * @phpstan-type Crumb array{label: string, url: ?string}
 */
final readonly class SchemaContext
{
    /**
     * @param  array<int, array{label: string, url: ?string}>  $breadcrumbs
     * @param  Collection<int, Faq>  $faqs  FAQs actually rendered on the page
     */
    public function __construct(
        public ?Model $entity,
        public PageMeta $meta,
        public Indexability $indexability,
        public string $url,
        public string $siteUrl,
        public array $breadcrumbs = [],
        public ?Collection $faqs = null,
        public string $pageType = 'WebPage',
    ) {}

    public function organizationId(): string
    {
        return $this->siteUrl.'/#organization';
    }

    public function websiteId(): string
    {
        return $this->siteUrl.'/#website';
    }

    public function webPageId(): string
    {
        return $this->url.'#webpage';
    }

    public function breadcrumbId(): string
    {
        return $this->url.'#breadcrumb';
    }
}
