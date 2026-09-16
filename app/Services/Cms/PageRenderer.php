<?php

namespace App\Services\Cms;

use App\Cms\Blocks\BlockRenderer;
use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use App\Seo\MetaResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Turns a resolved entity into its public view. Public controllers and the signed preview
 * share this class, so drafts render through exactly the same templates (architecture §36).
 */
class PageRenderer
{
    public function __construct(
        private readonly MetaResolver $meta,
        private readonly Breadcrumbs $breadcrumbs,
        private readonly CtaResolver $ctas,
        private readonly RelatedContentResolver $related,
        private readonly BlockRenderer $blocks,
    ) {}

    public function render(Model $entity, bool $preview = false): View
    {
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

        $meta = $this->meta->forEntity($entity);

        return view($view, [
            'entity' => $entity,
            'meta' => $preview ? $meta->forPreview() : $meta,
            'breadcrumbs' => $this->breadcrumbs->for($entity),
            'cta' => $this->ctas->forEntity($entity),
            'blocks' => method_exists($entity, 'enabledBlocks') ? $this->blocks->prepare($entity->enabledBlocks(), $entity, $preview) : [],
            'related' => $this->relatedFor($entity),
            'preview' => $preview,
        ]);
    }

    /**
     * @return array<string, Collection>
     */
    protected function relatedFor(Model $entity): array
    {
        return match (true) {
            $entity instanceof Service => [
                'services' => $this->related->services($entity),
                'products' => $entity->products()->publiclyVisible()->ordered()->with('media')->limit(3)->get(),
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
