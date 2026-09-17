<?php

namespace App\Seo\Schema\Builders;

use App\Models\ProductDocument;
use App\Seo\Schema\Concerns\OmitsEmptyValues;
use App\Seo\Schema\SchemaBuilder;
use App\Seo\Schema\SchemaContext;
use App\Services\Cms\PublicUrl;

/**
 * TechArticle for product documentation, linked to the product it documents.
 */
class ProductDocumentBuilder implements SchemaBuilder
{
    use OmitsEmptyValues;

    public function __construct(private readonly PublicUrl $urls) {}

    public function supports(SchemaContext $context): bool
    {
        return $context->entity instanceof ProductDocument;
    }

    public function build(SchemaContext $context): array
    {
        /** @var ProductDocument $document */
        $document = $context->entity;
        $product = $document->relationLoaded('product') ? $document->product : null;

        return [$this->compact([
            '@type' => 'TechArticle',
            '@id' => $context->url.'#techarticle',
            'headline' => $document->title,
            'description' => $this->text($document->excerpt) ?? $this->text($context->meta->description),
            'url' => $context->url,
            'articleSection' => $document->section,
            'mainEntityOfPage' => ['@id' => $context->webPageId()],
            'datePublished' => $context->meta->publishedTime,
            'dateModified' => $context->meta->modifiedTime,
            'about' => $product ? $this->compact(['@type' => 'SoftwareApplication', 'name' => $product->name, 'url' => $this->urls->urlFor($product)]) : null,
            'publisher' => ['@id' => $context->organizationId()],
        ])];
    }
}
