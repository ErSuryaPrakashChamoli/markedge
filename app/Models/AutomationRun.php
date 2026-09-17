<?php

namespace App\Models;

use App\Enums\AutomationRunStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One attempt record per (rule, subject, occurrence). The unique idempotency key is what makes
 * retries and duplicate events safe.
 */
#[Fillable(['automation_rule_id', 'subject_type', 'subject_id', 'idempotency_key', 'status', 'attempts', 'result', 'error', 'started_at', 'finished_at'])]
class AutomationRun extends Model
{
    protected function casts(): array
    {
        return [
            'status' => AutomationRunStatus::class,
            'result' => 'array',
            'attempts' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }
}
