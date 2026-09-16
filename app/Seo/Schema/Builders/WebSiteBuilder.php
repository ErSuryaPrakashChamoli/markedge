<?php

namespace App\Seo\Schema\Builders;

use App\Seo\Schema\Concerns\OmitsEmptyValues;
use App\Seo\Schema\SchemaBuilder;
use App\Seo\Schema\SchemaContext;
use App\Services\Cms\Settings;

/**
 * WebSite without a SearchAction: public search does not exist yet, so none is claimed.
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
        ])];
    }
}
