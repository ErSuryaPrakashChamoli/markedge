<?php

namespace App\Seo\Schema\Builders;

use App\Models\Product;
use App\Seo\Schema\Concerns\OmitsEmptyValues;
use App\Seo\Schema\SchemaBuilder;
use App\Seo\Schema\SchemaContext;

/**
 * SoftwareApplication with descriptive properties only. Offers, prices, ratings and reviews
 * are never emitted because the content model holds no such data (architecture §16.1).
 */
class ProductBuilder implements SchemaBuilder
{
    use OmitsEmptyValues;

    public function supports(SchemaContext $context): bool
    {
        return $context->entity instanceof Product;
    }

    public function build(SchemaContext $context): array
    {
        /** @var Product $product */
        $product = $context->entity;

        return [$this->compact([
            '@type' => 'SoftwareApplication',
            '@id' => $context->url.'#product',
            'name' => $product->name,
            'alternateName' => $product->tagline,
            'description' => $this->text($product->short_description) ?? $this->text($context->meta->description),
            'url' => $context->url,
            'applicationCategory' => $product->product_type,
            'image' => $context->meta->ogImage,
            'provider' => ['@id' => $context->organizationId()],
            'mainEntityOfPage' => ['@id' => $context->webPageId()],
        ])];
    }
}
