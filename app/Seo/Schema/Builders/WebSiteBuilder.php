<?php

namespace App\Seo\Schema\Builders;

use App\Seo\Schema\Concerns\OmitsEmptyValues;
use App\Seo\Schema\SchemaBuilder;
use App\Seo\Schema\SchemaContext;
use App\Services\Cms\Settings;

/**
 * WebSite with a SearchAction pointing at the real /search?q= endpoint.
 */
class WebSiteBuilder implements SchemaBuilder
{
    use OmitsEmptyValues;

    public function __construct(private readonly Settings $settings) {}

    public function supports(SchemaContext $context): bool
    {
        return true;
    }

    public function build(SchemaContext $context): array
    {
        return [$this->compact([
            '@type' => 'WebSite',
            '@id' => $context->websiteId(),
            'name' => $this->settings->get('company.name', config('app.name')),
            'url' => $context->siteUrl,
            'publisher' => ['@id' => $context->organizationId()],
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $context->siteUrl.'/search?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ])];
    }
}
