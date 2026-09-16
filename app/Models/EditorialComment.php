<?php

namespace App\Models;

use App\Enums\EditorialCommentType;
use Database\Factories\EditorialCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Internal editorial note or change request on a content record. Never rendered publicly.
 */
#[Fillable(['commentable_type', 'commentable_id', 'user_id', 'type', 'body', 'resolved_at', 'resolved_by'])]
class EditorialComment extends Model
{
    /** @use HasFactory<EditorialCommentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => EditorialCommentType::class,
            'resolved_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function unresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }
}
