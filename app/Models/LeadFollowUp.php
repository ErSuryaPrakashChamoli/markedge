<?php

namespace App\Models;

use App\Enums\FollowUpType;
use Database\Factories\LeadFollowUpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A dated task on a lead, owned by one user. Completion is recorded, never deleted.
 */
#[Fillable(['lead_id', 'user_id', 'created_by', 'type', 'due_at', 'note', 'outcome', 'completed_at', 'completed_by', 'reminded_at'])]
class LeadFollowUp extends Model
{
    /** @use HasFactory<LeadFollowUpFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => FollowUpType::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'reminded_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    #[Scope]
    protected function overdue(Builder $query): Builder
    {
        return $query->whereNull('completed_at')->where('due_at', '<', now());
    }

    #[Scope]
    protected function dueWithinHours(Builder $query, int $hours): Builder
    {
        return $query->whereNull('completed_at')->where('due_at', '<=', now()->addHours($hours));
    }

    #[Scope]
    protected function ownedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isOverdue(): bool
    {
        return $this->completed_at === null && $this->due_at->isPast();
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
