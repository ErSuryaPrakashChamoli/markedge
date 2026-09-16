<?php

namespace App\Models\Concerns;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared publishing lifecycle: status + published_at, with the public scope
 * used by every route binding and listing.
 */
trait Publishable
{
    public function initializePublishable(): void
    {
        $this->mergeCasts([
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ]);
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query
            ->where('status', PublishStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    #[Scope]
    protected function scheduled(Builder $query): Builder
    {
        return $query
            ->where('status', PublishStatus::Scheduled)
            ->whereNotNull('published_at');
    }

    #[Scope]
    protected function dueForPublishing(Builder $query): Builder
    {
        return $query->scheduled()->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === PublishStatus::Published
            && $this->published_at !== null
            && $this->published_at->lessThanOrEqualTo(now());
    }

    public function publish(): static
    {
        $this->forceFill([
            'status' => PublishStatus::Published,
            'published_at' => $this->published_at?->isPast() ? $this->published_at : now(),
        ])->save();

        return $this;
    }

    public function unpublish(): static
    {
        $this->forceFill(['status' => PublishStatus::Draft])->save();

        return $this;
    }

    public function archive(): static
    {
        $this->forceFill(['status' => PublishStatus::Archived])->save();

        return $this;
    }
}
