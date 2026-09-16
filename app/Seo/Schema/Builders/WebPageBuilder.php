<?php

namespace App\Seo\Schema\Builders;

use App\Seo\Schema\Concerns\OmitsEmptyValues;
use App\Seo\Schema\SchemaBuilder;
use App\Seo\Schema\SchemaContext;

class WebPageBuilder implements SchemaBuilder
{
    use OmitsEmptyValues;

    public function supports(SchemaContext $context): bool
    {
        return true;
    }

    public function build(SchemaContext $context): array
    {
        $entity = $context->entity;

        return [$this->compact([
            '@type' => $context->pageType,
            '@id' => $context->webPageId(),
            'url' => $context->url,
            'name' => $this->text($entity?->title ?? $entity?->name ?? $context->meta->title),
            'description' => $this->text($context->meta->description),
            'isPartOf' => ['@id' => $context->websiteId()],
            'about' => ['@id' => $context->organizationId()],
            'breadcrumb' => $context->breadcrumbs !== [] ? ['@id' => $context->breadcrumbId()] : null,
            'primaryImageOfPage' => $context->meta->ogImage ? ['@type' => 'ImageObject', 'url' => $context->meta->ogImage] : null,
            'datePublished' => $context->meta->publishedTime,
            'dateModified' => $context->meta->modifiedTime,
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
        ])];
    }
}
