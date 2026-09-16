<?php

namespace App\Seo\Diagnostics;

use App\Enums\ProductStatus;
use App\Models\Article;
use App\Models\Product;
use App\Seo\IndexabilityResolver;
use App\Seo\MetaResolver;
use App\Seo\Schema\SchemaContext;
use App\Seo\Schema\SchemaGraphBuilder;
use App\Services\Cms\Breadcrumbs;
use Illuminate\Database\Eloquent\Model;

/**
 * Factual SEO checks for one record. No scores: each check is required, recommended or
 * informational and reports what is true (Phase 6 §6).
 */
class SeoAudit
{
    public const int TITLE_ADVISORY = 70;

    public const int DESCRIPTION_ADVISORY = 200;

    public function __construct(
        private readonly IndexabilityResolver $indexability,
        private readonly MetaResolver $meta,
        private readonly Breadcrumbs $breadcrumbs,
        private readonly SchemaGraphBuilder $schema,
    ) {}

    /**
     * @return array<int, SeoCheck>
     */
    public function forEntity(Model $entity): array
    {
        $seo = $this->indexability->seoFor($entity);
        $decision = $this->indexability->forEntity($entity);
        $meta = $this->meta->forEntity($entity);
        $checks = [];

        $status = $entity instanceof Product ? $entity->status->getLabel() : ($entity->status?->getLabel() ?? '—');
        $isPublic = $entity instanceof Product ? in_array($entity->status, ProductStatus::publiclyVisible(), true) : (method_exists($entity, 'isPublished') && $entity->isPublished());
        $checks[] = new SeoCheck('status', 'Publication status', SeoCheck::INFO, $isPublic ? SeoCheck::PASS : SeoCheck::NOTE, $status.($isPublic ? ' — publicly visible' : ' — not on the public website'));
        $checks[] = new SeoCheck('slug', 'Public URL', SeoCheck::INFO, SeoCheck::NOTE, $decision->canonical ?? 'No public URL for this record');

        $checks[] = new SeoCheck('indexability', 'Indexability', SeoCheck::INFO, $decision->indexable ? SeoCheck::PASS : SeoCheck::NOTE, ucfirst($decision->reason).' (robots: '.$decision->robots().')');

        $checks[] = match (true) {
            ! $isPublic => new SeoCheck('canonical', 'Canonical URL', SeoCheck::REQUIRED, SeoCheck::NOTE, 'Available once the record is published'),
            $decision->canonical === null => new SeoCheck('canonical', 'Canonical URL', SeoCheck::REQUIRED, SeoCheck::FAIL, 'No canonical URL could be built'),
            ! filter_var($decision->canonical, FILTER_VALIDATE_URL) => new SeoCheck('canonical', 'Canonical URL', SeoCheck::REQUIRED, SeoCheck::FAIL, 'Canonical is not a valid absolute URL: '.$decision->canonical),
            $decision->canonicalizedElsewhere => new SeoCheck('canonical', 'Canonical URL', SeoCheck::REQUIRED, SeoCheck::NOTE, 'Points to another URL: '.$decision->canonical.' (excluded from sitemap and schema)'),
            default => new SeoCheck('canonical', 'Canonical URL', SeoCheck::REQUIRED, SeoCheck::PASS, 'Self-referencing: '.$decision->canonical),
        };

        $checks[] = new SeoCheck('sitemap', 'Sitemap', SeoCheck::INFO, $decision->inSitemap ? SeoCheck::PASS : SeoCheck::NOTE, $decision->inSitemap ? 'Included' : 'Excluded'.(($seo?->include_in_sitemap ?? true) ? '' : ' by the sitemap setting'));

        $types = [];

        if ($decision->schemaEligible && $decision->canonical) {
            $graph = $this->schema->build(new SchemaContext($entity, $meta, $decision, $decision->canonical, $this->indexability->siteUrl(), $this->breadcrumbs->for($entity), method_exists($entity, 'faqs') ? $entity->faqs()->visible()->get() : null));
            $types = array_values(array_unique(array_map(fn (array $node) => (string) ($node['@type'] ?? ''), $graph)));
        }

        $checks[] = new SeoCheck('schema', 'Structured data', SeoCheck::INFO, $types !== [] ? SeoCheck::PASS : SeoCheck::NOTE, $types !== [] ? 'Emits: '.implode(', ', $types) : 'Not eligible');

        $titleLength = mb_strlen($meta->title);
        $checks[] = filled($seo?->title)
            ? new SeoCheck('title', 'SEO title', SeoCheck::RECOMMENDED, SeoCheck::PASS, "Set explicitly ({$titleLength} characters)")
            : new SeoCheck('title', 'SEO title', SeoCheck::RECOMMENDED, SeoCheck::WARN, "Using the fallback \"{$meta->title}\" ({$titleLength} characters)");

        if ($titleLength > self::TITLE_ADVISORY) {
            $checks[] = new SeoCheck('title_length', 'Title length', SeoCheck::INFO, SeoCheck::NOTE, "{$titleLength} characters is longer than the ".self::TITLE_ADVISORY.'-character guideline. This is allowed; search engines may shorten it.');
        }

        $descriptionLength = mb_strlen((string) $meta->description);
        $checks[] = match (true) {
            filled($seo?->description) => new SeoCheck('description', 'Meta description', SeoCheck::RECOMMENDED, SeoCheck::PASS, "Set explicitly ({$descriptionLength} characters)"),
            filled($meta->description) => new SeoCheck('description', 'Meta description', SeoCheck::RECOMMENDED, SeoCheck::WARN, "Using content fallback ({$descriptionLength} characters)"),
            default => new SeoCheck('description', 'Meta description', SeoCheck::RECOMMENDED, SeoCheck::FAIL, 'No description available from SEO settings, content or global defaults'),
        };

        if ($descriptionLength > self::DESCRIPTION_ADVISORY) {
            $checks[] = new SeoCheck('description_length', 'Description length', SeoCheck::INFO, SeoCheck::NOTE, "{$descriptionLength} characters is longer than the ".self::DESCRIPTION_ADVISORY.'-character guideline. This is allowed.');
        }

        $checks[] = $meta->ogImage
            ? new SeoCheck('og_image', 'Sharing image', SeoCheck::RECOMMENDED, SeoCheck::PASS, $seo?->getFirstMedia('og_image') ? 'Dedicated Open Graph image' : 'Falls back to the record or global image')
            : new SeoCheck('og_image', 'Sharing image', SeoCheck::RECOMMENDED, SeoCheck::WARN, 'No Open Graph image, record image or global default image');

        $crumbs = $this->breadcrumbs->for($entity);
        $checks[] = new SeoCheck('breadcrumbs', 'Breadcrumbs', SeoCheck::INFO, $crumbs !== [] ? SeoCheck::PASS : SeoCheck::NOTE, $crumbs !== [] ? implode(' › ', ['Home', ...array_column($crumbs, 'label')]) : 'This page has no breadcrumb trail');

        if (method_exists($entity, 'faqs')) {
            $faqCount = $entity->faqs()->visible()->count();
            $checks[] = new SeoCheck('faqs', 'FAQ content', SeoCheck::INFO, $faqCount > 0 ? SeoCheck::PASS : SeoCheck::NOTE, $faqCount > 0 ? "{$faqCount} visible FAQ(s) — FAQPage schema emitted" : 'No FAQs — no FAQPage schema');
        }

        if ($entity instanceof Article) {
            $checks[] = new SeoCheck('article_author', 'Article author', SeoCheck::RECOMMENDED, $entity->author ? SeoCheck::PASS : SeoCheck::WARN, $entity->author?->name ?? 'No author assigned');
            $checks[] = new SeoCheck('article_dates', 'Article dates', SeoCheck::RECOMMENDED, $entity->published_at ? SeoCheck::PASS : SeoCheck::WARN, $entity->published_at ? 'Published '.$entity->published_at->format('d M Y') : 'No publication date yet');
        }

        return $checks;
    }
}
