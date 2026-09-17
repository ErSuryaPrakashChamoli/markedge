<?php

namespace App\Editorial;

use App\Cms\Blocks\BlockRegistry;
use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Concerns\HasBlocks;
use App\Models\LandingPage;
use App\Seo\IndexabilityResolver;
use App\Services\Cms\CtaResolver;
use App\Services\Cms\RelatedContentResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Actionable, factual diagnostics for one record. No score: each finding names the fix.
 */
class ContentHealthAuditor
{
    public function __construct(
        private readonly BlockRegistry $blocks,
        private readonly IndexabilityResolver $indexability,
        private readonly CtaResolver $ctas,
        private readonly RelatedContentResolver $related,
        private readonly ContentHealthScanner $scanner,
    ) {}

    /**
     * @return array<int, array{key: string, severity: string, message: string}>
     */
    public function check(Model $record): array
    {
        if (method_exists($record, 'seo')) {
            $record->loadMissing('seo');
        }

        $issues = [];
        $add = function (string $key, string $severity, string $message) use (&$issues): void {
            $issues[] = ['key' => $key, 'severity' => $severity, 'message' => $message];
        };

        if (blank($record->title ?? $record->name ?? null)) {
            $add('missing_title', 'error', 'The title is empty.');
        }

        $summary = $record->excerpt ?? $record->short_description ?? $record->tagline ?? null;
        $seo = method_exists($record, 'seo') ? $record->seo : null;

        if (! $record instanceof LandingPage && blank($summary) && blank($seo?->description)) {
            $add('missing_description', 'warning', 'No summary and no SEO description: search snippets and cards will be empty.');
        }

        if ($record instanceof Article && ! $record->getMedia('featured')->count()) {
            $add('missing_image', 'warning', 'Articles need a featured image for cards and social sharing.');
        }

        if ($record instanceof CaseStudy && ! $record->getMedia('hero')->count()) {
            $add('missing_image', 'warning', 'Case studies need a hero image.');
        }

        if ($record instanceof Article && $record->author_id === null) {
            $add('missing_author', 'warning', 'No author selected.');
        }

        if ($record instanceof Article && $record->article_category_id === null) {
            $add('missing_category', 'warning', 'No category selected; the article will not appear in category listings.');
        }

        if (in_array(HasBlocks::class, class_uses_recursive($record), true)) {
            foreach ($this->blocks->validate($record->blocks, $record->getMorphClass())->all() as $message) {
                $add('invalid_block', 'error', $message);
            }
        }

        if ($this->ctas->forEntity($record) === null) {
            $add('missing_cta', 'warning', 'No call to action resolves for this content (entity, parent or default CTA).');
        }

        if ($this->related->discover($record, 1)->isEmpty()) {
            $add('no_related_content', 'info', 'No related content will show; link services, solutions, articles or case studies.');
        }

        foreach ($this->scanner->brokenLinksIn($record) as $link) {
            $add('broken_link', 'error', "Internal link {$link} does not resolve to a public page.");
        }

        foreach (['services', 'products', 'solutions', 'industries', 'relatedArticles', 'relatedServices', 'caseStudies'] as $relation) {
            if (! method_exists($record, $relation) || ! ($query = $record->{$relation}()) instanceof BelongsToMany) {
                continue;
            }

            $related = $query->get();
            $hidden = $related->reject(fn (Model $item): bool => method_exists($item, 'isPubliclyVisible') ? $item->isPubliclyVisible() : (method_exists($item, 'isPublished') ? $item->isPublished() : true));

            if ($hidden->isNotEmpty()) {
                $add('unpublished_link', 'warning', ucfirst($relation).' links to unpublished content: '.$hidden->map(fn (Model $item) => WorkflowModels::titleOf($item))->join(', ').'.');
            }
        }

        $defaultIndex = ! $record instanceof LandingPage;

        if (($seo?->robots_index ?? $defaultIndex) === false) {
            $add('noindex', 'info', $seo && $seo->robots_index === false ? 'Noindex is set in SEO settings.' : 'Noindex by default for this content type.');
        }

        if (filled($seo?->canonical_url)) {
            $add('canonicalized', 'info', 'Canonical points to '.$seo->canonical_url.'; this URL will not rank on its own.');
        }

        if ($record->isPublished()) {
            $decision = $this->indexability->forEntity($record);

            if (! $decision->indexable && $decision->reason === 'environment is not indexable') {
                $add('environment', 'info', 'This environment is closed to search engines (MARKEDGE_INDEXABLE).');
            }
        }

        if (method_exists($record, 'hasExpired') && $record->hasExpired()) {
            $add('expired', 'warning', 'The unpublish date has passed.');
        }

        if (isset($record->owner_id) === false || $record->owner_id === null) {
            $add('no_owner', 'info', 'No editorial owner assigned.');
        }

        return $issues;
    }
}
