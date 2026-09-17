<?php

namespace App\Search;

use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;

/**
 * Content types that take part in public discovery, with their public labels and base weights.
 * Leads, users, forms, SEO rows and every other internal record are never eligible.
 */
class SearchTypes
{
    /** @var array<string, array{model: class-string, label: string, plural: string, weight: int}> */
    public const array TYPES = [
        'service' => ['model' => Service::class, 'label' => 'Service', 'plural' => 'Services', 'weight' => 30],
        'product' => ['model' => Product::class, 'label' => 'Product', 'plural' => 'Products', 'weight' => 30],
        'product_document' => ['model' => ProductDocument::class, 'label' => 'Documentation', 'plural' => 'Documentation', 'weight' => 12],
        'service_category' => ['model' => ServiceCategory::class, 'label' => 'Service area', 'plural' => 'Service areas', 'weight' => 25],
        'solution' => ['model' => Solution::class, 'label' => 'Solution', 'plural' => 'Solutions', 'weight' => 25],
        'industry' => ['model' => Industry::class, 'label' => 'Industry', 'plural' => 'Industries', 'weight' => 20],
        'case_study' => ['model' => CaseStudy::class, 'label' => 'Case study', 'plural' => 'Case studies', 'weight' => 20],
        'article' => ['model' => Article::class, 'label' => 'Article', 'plural' => 'Articles', 'weight' => 10],
        'page' => ['model' => Page::class, 'label' => 'Page', 'plural' => 'Pages', 'weight' => 15],
    ];

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::TYPES);
    }

    public static function label(string $key): string
    {
        return self::TYPES[$key]['label'] ?? ucfirst(str_replace('_', ' ', $key));
    }

    public static function plural(string $key): string
    {
        return self::TYPES[$key]['plural'] ?? self::label($key);
    }

    public static function weight(string $key): int
    {
        return self::TYPES[$key]['weight'] ?? 0;
    }

    /**
     * @return class-string|null
     */
    public static function modelFor(string $key): ?string
    {
        return self::TYPES[$key]['model'] ?? null;
    }

    public static function keyFor(object $model): ?string
    {
        foreach (self::TYPES as $key => $definition) {
            if ($model instanceof $definition['model']) {
                return $key;
            }
        }

        return null;
    }
}
