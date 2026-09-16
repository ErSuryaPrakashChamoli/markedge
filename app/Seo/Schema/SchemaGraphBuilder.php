<?php

namespace App\Seo\Schema;

use App\Filament\Support\SeoFields;
use App\Seo\IndexabilityResolver;
use App\Seo\Schema\Builders\ArticleBuilder;
use App\Seo\Schema\Builders\BreadcrumbBuilder;
use App\Seo\Schema\Builders\FaqBuilder;
use App\Seo\Schema\Builders\OrganizationBuilder;
use App\Seo\Schema\Builders\ProductBuilder;
use App\Seo\Schema\Builders\ServiceBuilder;
use App\Seo\Schema\Builders\WebPageBuilder;
use App\Seo\Schema\Builders\WebSiteBuilder;

/**
 * Assembles one coherent @graph from the registered builders, then applies the entity's
 * validated overrides to its primary node (architecture §16.2).
 */
class SchemaGraphBuilder
{
    /** @var array<int, class-string<SchemaBuilder>> */
    public const array BUILDERS = [
        OrganizationBuilder::class,
        WebSiteBuilder::class,
        WebPageBuilder::class,
        BreadcrumbBuilder::class,
        ServiceBuilder::class,
        ArticleBuilder::class,
        ProductBuilder::class,
        FaqBuilder::class,
    ];

    /** @var array<int, string> */
    public const array PROTECTED_KEYS = ['@context', '@type', '@id', '@graph'];

    public function __construct(private readonly IndexabilityResolver $indexability) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function build(SchemaContext $context): array
    {
        if (! $context->indexability->schemaEligible) {
            return [];
        }

        $nodes = [];

        foreach (self::BUILDERS as $class) {
            $builder = app($class);

            if ($builder->supports($context)) {
                foreach ($builder->build($context) as $node) {
                    if (isset($node['@id'])) {
                        $nodes[$node['@id']] = $node;
                    } else {
                        $nodes[] = $node;
                    }
                }
            }
        }

        return array_values($this->applyOverrides($nodes, $context));
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $nodes
     * @return array<int|string, array<string, mixed>>
     */
    protected function applyOverrides(array $nodes, SchemaContext $context): array
    {
        $seo = $context->entity ? $this->indexability->seoFor($context->entity) : null;

        if ($seo === null) {
            return $nodes;
        }

        $primaryId = $this->primaryNodeId($nodes, $context);

        if ($primaryId === null) {
            return $nodes;
        }

        if (filled($seo->schema_type) && array_key_exists($seo->schema_type, SeoFields::SCHEMA_TYPES)) {
            $nodes[$primaryId]['@type'] = $seo->schema_type;
        }

        $overrides = is_array($seo->schema_overrides) ? $seo->schema_overrides : [];

        foreach ($this->sanitise($overrides) as $key => $value) {
            if (! in_array($key, self::PROTECTED_KEYS, true)) {
                $nodes[$primaryId][$key] = $value;
            }
        }

        return $nodes;
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $nodes
     */
    protected function primaryNodeId(array $nodes, SchemaContext $context): ?string
    {
        foreach (['#service', '#article', '#product'] as $suffix) {
            if (isset($nodes[$context->url.$suffix])) {
                return $context->url.$suffix;
            }
        }

        return isset($nodes[$context->webPageId()]) ? $context->webPageId() : null;
    }

    /**
     * Keeps only JSON-safe scalars and nested arrays; anything else is dropped.
     *
     * @param  array<int|string, mixed>  $values
     * @return array<int|string, mixed>
     */
    protected function sanitise(array $values, int $depth = 0): array
    {
        if ($depth > 5) {
            return [];
        }

        $clean = [];

        foreach ($values as $key => $value) {
            if (! is_string($key) && ! is_int($key)) {
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitise($value, $depth + 1);
            } elseif (is_string($value)) {
                $clean[$key] = trim(strip_tags($value));
            } elseif (is_int($value) || is_float($value) || is_bool($value)) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    /**
     * HTML-safe JSON for a <script type="application/ld+json"> element.
     *
     * @param  array<int, array<string, mixed>>  $graph
     */
    public static function encode(array $graph): string
    {
        return (string) json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        );
    }
}
