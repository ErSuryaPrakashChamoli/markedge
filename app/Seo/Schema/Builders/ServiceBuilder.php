<?php

namespace App\Seo\Schema\Builders;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Seo\Schema\Concerns\OmitsEmptyValues;
use App\Seo\Schema\SchemaBuilder;
use App\Seo\Schema\SchemaContext;

/**
 * Service schema without offers, prices or ratings (architecture §16.1).
 */
class ServiceBuilder implements SchemaBuilder
{
    use OmitsEmptyValues;

    public function supports(SchemaContext $context): bool
    {
        return $context->entity instanceof Service || $context->entity instanceof ServiceCategory;
    }

    public function build(SchemaContext $context): array
    {
        $entity = $context->entity;

        return [$this->compact([
            '@type' => 'Service',
            '@id' => $context->url.'#service',
            'name' => $entity->name,
            'serviceType' => $entity->name,
            'description' => $this->text($entity->short_description ?: $entity->tagline) ?? $this->text($context->meta->description),
            'url' => $context->url,
            'provider' => ['@id' => $context->organizationId()],
            'category' => $entity instanceof Service && $entity->relationLoaded('category') ? $entity->category?->name : null,
            'image' => $context->meta->ogImage,
            'mainEntityOfPage' => ['@id' => $context->webPageId()],
        ])];
    }
}
