<?php

namespace App\Services\Cms;

use App\Cms\Blocks\BlockRenderer;
use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Faq;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use App\Seo\SeoEngine;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Turns a resolved entity into its public view. Public controllers and the signed preview
 * share this class, so drafts render through exactly the same templates (architecture §36).
 */
class PageRenderer
{
    public function __construct(
        private readonly SeoEngine $seo,
        private readonly Breadcrumbs $breadcrumbs,
        private readonly CtaResolver $ctas,
        private readonly RelatedContentResolver $related,
        private readonly BlockRenderer $blocks,
    ) {}

    public function render(Model $entity, bool $preview = false): View
    {
        request()?->attributes->set('markedge.entity', $entity);

        $view = match (true) {
            $entity instanceof Page => $entity->isHome() ? 'pages.home' : 'pages.page',
            $entity instanceof ServiceCategory => 'pages.services.category',
            $entity instanceof Service => 'pages.services.show',
            $entity instanceof Product => 'pages.products.show',
            $entity instanceof Solution => 'pages.solutions.show',
            $entity instanceof Industry => 'pages.industries.show',
            $entity instanceof CaseStudy => 'pages.case-studies.show',
            $entity instanceof Article => 'pages.insights.show',
            $entity instanceof LandingPage => 'pages.landing.show',
            default => throw new \InvalidArgumentException('No public template for '.$entity::class),
        };

        $breadcrumbs = $this->breadcrumbs->for($entity);
        $blocks = method_exists($entity, 'enabledBlocks') ? $this->blocks->prepare($entity->enabledBlocks(), $entity, $preview) : [];

        return view($view, [
            'entity' => $entity,
            'meta' => $this->seo->forEntity($entity, $breadcrumbs, $this->faqsOnPage($entity, $blocks, $view), $preview),
            'breadcrumbs' => $breadcrumbs,
            'cta' => $this->ctas->forEntity($entity),
            'blocks' => $blocks,
            'related' => $this->relatedFor($entity),
            'preview' => $preview,
        ]);
    }

    /**
     * FAQs the template will actually render: the entity's own visible FAQs (every skeleton
     * except the blocks-only home page) plus any faq blocks. Drives FAQPage schema.
     *
     * @param  array<int, array{key: string, data: array<string, mixed>}>  $blocks
     * @return SupportCollection<int, Faq>
     */
    protected function faqsOnPage(Model $entity, array $blocks, string $view): SupportCollection
    {
        $faqs = new SupportCollection;

        if ($view !== 'pages.home' && $entity->relationLoaded('faqs')) {
            $faqs = $faqs->concat($entity->faqs);
        }

        foreach ($blocks as $block) {
            if ($block['key'] === 'faq' && isset($block['data']['faqs'])) {
                $faqs = $faqs->concat($block['data']['faqs']);
            }
        }

        return $faqs->unique('id')->values();
    }

    /**
     * @return array<string, Collection>
     */
    protected function relatedFor(Model $entity): array
    {
        return match (true) {
            $entity instanceof Service => [
                'services' => $this->related->services($entity),
                'products' => $this->related->products($entity),
                'solutions' => $this->related->solutions($entity),
                'industries' => $this->related->industries($entity),
                'articles' => $this->related->articles($entity),
                'caseStudies' => $this->related->caseStudies($entity),
            ],
            $entity instanceof Product => [
                'services' => $this->related->services($entity),
                'articles' => $this->related->articles($entity),
            ],
            $entity instanceof Solution => [
                'articles' => $this->related->articles($entity),
                'caseStudies' => $this->related->caseStudies($entity),
            ],
            $entity instanceof Industry => [
                'articles' => $this->related->articles($entity),
            ],
            $entity instanceof Article => [
                'services' => $this->related->services($entity),
                'products' => $this->related->products($entity),
                'solutions' => $this->related->solutions($entity),
                'industries' => $this->related->industries($entity),
                'articles' => $this->related->articles($entity),
            ],
            $entity instanceof ServiceCategory => [
                'products' => $this->related->products($entity),
            ],
            default => [],
        };
    }
}
