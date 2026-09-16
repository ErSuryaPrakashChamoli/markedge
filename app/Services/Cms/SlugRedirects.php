<?php

namespace App\Services\Cms;

use App\Enums\ProductStatus;
use App\Enums\PublishStatus;
use App\Enums\RedirectStatus;
use App\Models\ArticleCategory;
use App\Models\Author;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Creates a permanent redirect when a publicly visible record changes slug (architecture §34).
 * Draft records, unchanged slugs and records without a public URL never produce redirects.
 */
class SlugRedirects
{
    public function __construct(private readonly PublicUrl $urls) {}

    public function afterSlugChange(Model $model): void
    {
        $oldSlug = $model->getOriginal('slug');
        $newSlug = $model->slug;

        if (blank($oldSlug) || $oldSlug === $newSlug || ! $this->wasPubliclyVisible($model)) {
            return;
        }

        $newPath = $this->urls->pathFor($model);
        $before = (clone $model)->setRawAttributes(['slug' => $oldSlug] + $model->getAttributes());
        $oldPath = $this->urls->pathFor($before);

        if ($newPath === null || $oldPath === null || $newPath === $oldPath) {
            return;
        }

        // A redirect that would send the new URL back to the old one would loop; drop it.
        Redirect::query()->where('from_path', $newPath)->delete();

        // Earlier redirects that pointed at the old URL now point straight to the new one.
        Redirect::query()->where('to_url', $oldPath)->update(['to_url' => $newPath, 'updated_at' => now()]);

        Redirect::query()->updateOrCreate(
            ['from_path' => $oldPath],
            ['to_url' => $newPath, 'status_code' => RedirectStatus::MovedPermanently, 'is_active' => true, 'notes' => 'Automatic: slug changed on '.class_basename($model).' #'.$model->getKey()],
        );
    }

    /**
     * Uses the record's state before this save, so a draft that changes slug creates nothing.
     */
    protected function wasPubliclyVisible(Model $model): bool
    {
        if ($model instanceof Product) {
            return in_array(ProductStatus::tryFrom((string) $model->getRawOriginal('status')), ProductStatus::publiclyVisible(), true);
        }

        if ($model instanceof ArticleCategory || $model instanceof Author) {
            return (bool) $model->getRawOriginal('is_visible');
        }

        if ($model instanceof Tag) {
            return true;
        }

        if (! array_key_exists('status', $model->getAttributes())) {
            return false;
        }

        $publishedAt = $model->getRawOriginal('published_at');

        return PublishStatus::tryFrom((string) $model->getRawOriginal('status')) === PublishStatus::Published
            && $publishedAt !== null
            && Carbon::parse($publishedAt)->lessThanOrEqualTo(now());
    }
}
