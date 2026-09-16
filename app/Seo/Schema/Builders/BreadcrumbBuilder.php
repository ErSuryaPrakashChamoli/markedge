<?php

namespace App\Seo\Schema\Builders;

use App\Seo\IndexabilityResolver;
use App\Seo\Schema\SchemaBuilder;
use App\Seo\Schema\SchemaContext;

/**
 * BreadcrumbList from the exact trail the template renders (architecture §4.3, Phase 6 §13).
 */
class BreadcrumbBuilder implements SchemaBuilder
{
    public function __construct(private readonly IndexabilityResolver $urls) {}

    public function supports(SchemaContext $context): bool
    {
        return $context->breadcrumbs !== [];
    }

    public function build(SchemaContext $context): array
    {
        $items = [['label' => 'Home', 'url' => '/'], ...$context->breadcrumbs];
        $elements = [];

        foreach ($items as $index => $crumb) {
            $element = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['label'],
            ];

            if (filled($crumb['url'] ?? null)) {
                $element['item'] = $this->urls->absolute($crumb['url']);
            } elseif (array_key_last($items) === $index) {
                $element['item'] = $context->url;
            }

            $elements[] = $element;
        }

        return [[
            '@type' => 'BreadcrumbList',
            '@id' => $context->breadcrumbId(),
            'itemListElement' => $elements,
        ]];
    }
}
