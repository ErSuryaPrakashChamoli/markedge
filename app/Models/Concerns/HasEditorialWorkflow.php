<?php

namespace App\Models\Concerns;

use App\Editorial\RevisionManager;
use App\Models\ContentRevision;
use App\Models\EditorialComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Ownership, review, approval, expiry, revisions and comments for publishable content (Phase 9).
 * Content edits clear a pending approval so nothing approved can change unnoticed.
 */
trait HasEditorialWorkflow
{
    public function initializeHasEditorialWorkflow(): void
    {
        $this->mergeFillable(['owner_id', 'reviewer_id', 'unpublish_at']);
        $this->mergeCasts([
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'unpublish_at' => 'datetime',
            'expiry_reminded_at' => 'datetime',
        ]);
    }

    public static function bootHasEditorialWorkflow(): void
    {
        static::saving(function (Model $model): void {
            if ($model->approved_at !== null && $model->exists && $model->hasContentChanges()) {
                $model->approved_at = null;
                $model->approved_by = null;
            }
        });

        static::saved(function (Model $model): void {
            app(RevisionManager::class)->capture($model);
        });

        static::forceDeleted(function (Model $model): void {
            $model->revisions()->delete();
            $model->editorialComments()->delete();
        });
    }

    /**
     * Attributes that belong to the content itself (snapshotted in revisions). Publication,
     * ownership and approval state are deliberately excluded.
     *
     * @return array<int, string>
     */
    public function revisionedAttributes(): array
    {
        return array_values(array_diff($this->getFillable(), [
            'status', 'published_at', 'unpublish_at', 'owner_id', 'reviewer_id', 'created_by', 'updated_by',
        ]));
    }

    public function hasContentChanges(): bool
    {
        return array_intersect(array_keys($this->getDirty()), $this->revisionedAttributes()) !== [];
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function hasExpired(): bool
    {
        return $this->unpublish_at !== null && $this->unpublish_at->isPast();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function revisions(): MorphMany
    {
        return $this->morphMany(ContentRevision::class, 'revisionable')->orderByDesc('version');
    }

    public function editorialComments(): MorphMany
    {
        return $this->morphMany(EditorialComment::class, 'commentable')->latest('id');
    }

    #[Scope]
    protected function dueForUnpublishing(Builder $query): Builder
    {
        return $query->published()->whereNotNull('unpublish_at')->where('unpublish_at', '<=', now());
    }

    #[Scope]
    protected function expiringWithin(Builder $query, int $days): Builder
    {
        return $query->published()->whereNotNull('unpublish_at')->whereBetween('unpublish_at', [now(), now()->addDays($days)]);
    }
}
