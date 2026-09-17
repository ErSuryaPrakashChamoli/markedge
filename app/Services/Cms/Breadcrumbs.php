<?php

namespace App\Services\Cms;

use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use Illuminate\Database\Eloquent\Model;

/**
 * Breadcrumb trails from entity hierarchy, not from menus (architecture §4.3).
 *
 * @phpstan-type Crumb array{label: string, url: ?string}
 */
class Breadcrumbs
{
    public function __construct(private readonly PublicUrl $urls) {}

    /**
     * @return array<int, array{label: string, url: ?string}>
     */
    public function for(Model $entity): array
    {
        return match (true) {
            $entity instanceof Page => $entity->isHome() ? [] : [$this->crumb($entity->title)],
            $entity instanceof ServiceCategory => [['label' => 'Services', 'url' => '/services'], $this->crumb($entity->name)],
            $entity instanceof Service => array_values(array_filter([
                ['label' => 'Services', 'url' => '/services'],
                $entity->category?->isPublished() ? ['label' => $entity->category->name, 'url' => $this->urls->pathFor($entity->category)] : null,
                $this->crumb($entity->name),
            ])),
            $entity instanceof Product => [['label' => 'Products', 'url' => '/products'], $this->crumb($entity->name)],
            $entity instanceof ProductDocument => [
                ['label' => 'Products', 'url' => '/products'],
                ['label' => $entity->product->name, 'url' => $this->urls->pathFor($entity->product)],
                ['label' => 'Documentation', 'url' => $this->urls->pathFor($entity->product).'/docs'],
                $this->crumb($entity->title),
            ],
            $entity instanceof Solution => [['label' => 'Solutions', 'url' => '/solutions'], $this->crumb($entity->name)],
            $entity instanceof Industry => [['label' => 'Industries', 'url' => '/industries'], $this->crumb($entity->name)],
            $entity instanceof CaseStudy => [['label' => 'Case Studies', 'url' => '/case-studies'], $this->crumb($entity->title)],
            $entity instanceof Article => array_values(array_filter([
                ['label' => 'Insights', 'url' => '/insights'],
                $entity->category?->is_visible ? ['label' => $entity->category->name, 'url' => $this->urls->pathFor($entity->category)] : null,
                $this->crumb($entity->title),
            ])),
            $entity instanceof LandingPage => [],
            default => [],
        };
    }

    /**
     * @return array{label: string, url: ?string}
     */
    protected function crumb(string $label): array
    {
        return ['label' => $label, 'url' => null];
    }
}
