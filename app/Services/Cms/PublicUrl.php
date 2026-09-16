<?php

namespace App\Services\Cms;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Author;
use App\Models\CaseStudy;
use App\Models\Concerns\Publishable;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;

/**
 * Canonical public path for every routable entity (architecture §3, §34).
 * Phase 5 registers routes on exactly these paths.
 */
class PublicUrl
{
    public function pathFor(Model $model): ?string
    {
        return match (true) {
            $model instanceof Page => $model->isHome() ? '/' : "/{$model->slug}",
            $model instanceof ServiceCategory, $model instanceof Service => "/services/{$model->slug}",
            $model instanceof Product => "/products/{$model->slug}",
            $model instanceof Solution => "/solutions/{$model->slug}",
            $model instanceof Industry => "/industries/{$model->slug}",
            $model instanceof CaseStudy => "/case-studies/{$model->slug}",
            $model instanceof Article => "/insights/{$model->slug}",
            $model instanceof ArticleCategory => "/insights/category/{$model->slug}",
            $model instanceof Author => "/insights/author/{$model->slug}",
            $model instanceof Tag => "/insights/tag/{$model->slug}",
            $model instanceof LandingPage => "/lp/{$model->slug}",
            default => null,
        };
    }

    public function urlFor(Model $model): ?string
    {
        $path = $this->pathFor($model);

        return $path === null ? null : url($path);
    }

    /**
     * Whether the entity currently has a public page.
     */
    public function isPubliclyVisible(Model $model): bool
    {
        if ($model instanceof Product) {
            return $model->isPubliclyVisible();
        }

        if (in_array(Publishable::class, class_uses_recursive($model), true)) {
            return $model->isPublished();
        }

        if ($model instanceof ArticleCategory || $model instanceof Author) {
            return (bool) $model->is_visible;
        }

        return true;
    }
}
