<?php

namespace App\Models\Concerns;

use App\Services\Cms\SlugRedirects;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Generates a unique slug from the model's source attribute when none is given.
 * Slugs are never regenerated automatically once set, so URLs stay stable.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function (Model $model): void {
            if (blank($model->slug)) {
                $model->slug = $model->generateUniqueSlug((string) $model->{$model->slugSource()});
            } else {
                $model->slug = $model->generateUniqueSlug($model->slug);
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('slug') && filled($model->slug)) {
                $model->slug = $model->generateUniqueSlug($model->slug);
            }
        });

        static::updated(function (Model $model): void {
            if ($model->wasChanged('slug')) {
                app(SlugRedirects::class)->afterSlugChange($model);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function slugSource(): string
    {
        return 'name';
    }

    /**
     * Slugs that must never be assigned to this model (e.g. reserved route prefixes).
     */
    protected function slugIsReserved(string $slug): bool
    {
        return false;
    }

    public function generateUniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: Str::random(8);
        $base = Str::limit($base, 180, '');
        $slug = $base;
        $suffix = 2;

        while ($this->slugIsReserved($slug) || $this->slugExists($slug)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        $query = static::query()->where('slug', $slug);

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            $query->withTrashed();
        }

        if ($this->exists) {
            $query->whereKeyNot($this->getKey());
        }

        return $query->exists();
    }
}
