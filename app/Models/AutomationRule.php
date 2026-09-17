<?php

namespace App\Models;

use App\Enums\AutomationTrigger;
use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\TracksAuthorship;
use Database\Factories\AutomationRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A configurable "when X, if conditions, do actions" rule (Phase 16). Conditions and actions are
 * validated against the vocabulary in App\Automation\RuleVocabulary; nothing here executes code.
 */
#[Fillable(['name', 'trigger', 'conditions', 'actions', 'is_active', 'sort_order'])]
class AutomationRule extends Model
{
    /** @use HasFactory<AutomationRuleFactory> */
    use HasFactory, RecordsActivity, TracksAuthorship;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['name', 'trigger', 'conditions', 'actions', 'is_active'];

    protected function casts(): array
    {
        return [
            'trigger' => AutomationTrigger::class,
            'conditions' => 'array',
            'actions' => 'array',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function forTrigger(Builder $query, AutomationTrigger $trigger): Builder
    {
        return $query->where('trigger', $trigger)->orderBy('sort_order')->orderBy('id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }
}
