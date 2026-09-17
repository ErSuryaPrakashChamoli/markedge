<?php

namespace App\Sales;

use App\Enums\FollowUpType;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Events\Sales\LeadAssigned;
use App\Events\Sales\LeadStageChanged;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowUp;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The one place a lead's sales state changes (Phase 14). Every stage move, assignment, note and
 * follow-up passes through here so the timeline, timestamps, analytics (via LeadObserver) and
 * notifications stay consistent. Stage moves lock the row and validate against the configured matrix.
 */
class LeadWorkflow
{
    public function transition(Lead $lead, LeadStatus $to, ?User $actor = null, ?string $reason = null): Lead
    {
        $from = null;

        $lead = DB::transaction(function () use ($lead, $to, $actor, $reason, &$from): Lead {
            $lead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
            $from = $lead->status;

            if ($from === $to) {
                return $lead;
            }

            if (! $this->allows($from, $to)) {
                throw ValidationException::withMessages(['status' => "A lead cannot move from {$from->getLabel()} to {$to->getLabel()}."]);
            }

            $reason = filled($reason) ? mb_substr(trim((string) $reason), 0, 80) : null;

            if ($to->requiresReason() && $reason === null) {
                throw ValidationException::withMessages(['lost_reason' => 'A reason is required when a lead is closed as '.strtolower($to->getLabel()).'.']);
            }

            $attributes = ['status' => $to, 'stage_entered_at' => now(), 'last_activity_at' => now()];

            if ($to === LeadStatus::Contacted && $lead->contacted_at === null) {
                $attributes['contacted_at'] = now();
            }

            if ($to->isClosed()) {
                $attributes['closed_at'] = now();
                $attributes['lost_reason'] = $to->isLost() ? $reason : null;
            } elseif ($from->isClosed()) {
                $attributes['closed_at'] = null;
                $attributes['lost_reason'] = null;
            }

            $lead->fill($attributes)->save();

            $this->record($lead, LeadActivityType::StageChanged, $actor, $to->isLost() ? null : $reason, [
                'from' => $from->value,
                'to' => $to->value,
                'reason' => $to->isLost() ? $reason : null,
            ]);

            return $lead;
        });

        if ($from !== null && $from !== $lead->status) {
            LeadStageChanged::dispatch($lead->id, $from, $lead->status, $actor?->id, $reason);
        }

        return $lead;
    }

    public function assign(Lead $lead, ?User $owner, ?User $actor = null, ?string $team = null): Lead
    {
        $lead = DB::transaction(function () use ($lead, $owner, $actor, $team): Lead {
            $lead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
            $previous = $lead->assigned_to;

            $lead->fill([
                'assigned_to' => $owner?->id,
                'team' => $team !== null ? mb_substr($team, 0, 80) : $lead->team,
                'last_activity_at' => now(),
            ])->save();

            if ($previous !== $lead->assigned_to) {
                $this->record($lead, LeadActivityType::Assigned, $actor, null, ['from' => $previous, 'to' => $lead->assigned_to]);
            }

            return $lead;
        });

        if ($owner !== null && $lead->status === LeadStatus::New && LeadStatus::New->canTransitionTo(LeadStatus::Assigned)) {
            $lead = $this->transition($lead, LeadStatus::Assigned, $actor);
        }

        if ($owner !== null) {
            LeadAssigned::dispatch($lead->id, $owner->id, $actor?->id);
        }

        return $lead;
    }

    public function addNote(Lead $lead, User $actor, string $body): LeadActivity
    {
        $body = trim($body);

        if ($body === '') {
            throw ValidationException::withMessages(['body' => 'A note needs some text.']);
        }

        $lead->forceFill(['last_activity_at' => now()])->saveQuietly();

        return $this->record($lead, LeadActivityType::Note, $actor, mb_substr($body, 0, 5000));
    }

    /**
     * @param  array<string, mixed>  $data  type, due_at, note, user_id
     */
    public function scheduleFollowUp(Lead $lead, User $actor, array $data): LeadFollowUp
    {
        $dueAt = $data['due_at'] instanceof Carbon ? $data['due_at'] : Carbon::parse((string) $data['due_at']);
        $type = $data['type'] instanceof FollowUpType ? $data['type'] : (FollowUpType::tryFrom((string) ($data['type'] ?? 'task')) ?? FollowUpType::Task);

        $followUp = DB::transaction(function () use ($lead, $actor, $data, $dueAt, $type): LeadFollowUp {
            $followUp = $lead->followUps()->create([
                'user_id' => $data['user_id'] ?? $lead->assigned_to ?? $actor->id,
                'created_by' => $actor->id,
                'type' => $type,
                'due_at' => $dueAt,
                'note' => filled($data['note'] ?? null) ? mb_substr(trim((string) $data['note']), 0, 2000) : null,
            ]);

            $this->syncNextFollowUp($lead);
            $this->record($lead, LeadActivityType::FollowUpScheduled, $actor, $followUp->note, ['follow_up_id' => $followUp->id, 'type' => $type->value, 'due_at' => $dueAt->toIso8601String(), 'user_id' => $followUp->user_id]);

            return $followUp;
        });

        return $followUp;
    }

    public function completeFollowUp(LeadFollowUp $followUp, User $actor, ?string $outcome = null): LeadFollowUp
    {
        return DB::transaction(function () use ($followUp, $actor, $outcome): LeadFollowUp {
            if ($followUp->completed_at !== null) {
                return $followUp;
            }

            $followUp->fill([
                'completed_at' => now(),
                'completed_by' => $actor->id,
                'outcome' => filled($outcome) ? mb_substr(trim((string) $outcome), 0, 2000) : null,
            ])->save();

            $lead = $followUp->lead;
            $this->syncNextFollowUp($lead);
            $this->record($lead, LeadActivityType::FollowUpCompleted, $actor, $followUp->outcome, ['follow_up_id' => $followUp->id, 'type' => $followUp->type->value]);

            return $followUp;
        });
    }

    /**
     * @param  array<string, mixed>|null  $answers
     */
    public function qualify(Lead $lead, User $actor, ?array $answers): Lead
    {
        $clean = QualificationFields::sanitise($answers);

        if ($clean === $lead->qualification) {
            return $lead;
        }

        $lead->fill(['qualification' => $clean, 'last_activity_at' => now()])->save();
        $this->record($lead, LeadActivityType::QualificationUpdated, $actor, null, ['fields' => array_keys($clean ?? [])]);

        return $lead;
    }

    public function allows(LeadStatus $from, LeadStatus $to): bool
    {
        return $from->canTransitionTo($to);
    }

    /**
     * @return array<string, string> value => label of stages reachable from the current one (plus the current one)
     */
    public function optionsFor(Lead $lead): array
    {
        $current = $lead->status ?? LeadStatus::New;
        $options = [$current->value => $current->getLabel()];

        foreach ($current->transitions() as $status) {
            $options[$status->value] = $status->getLabel();
        }

        $options[LeadStatus::Spam->value] = LeadStatus::Spam->getLabel();

        return $options;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(Lead $lead, LeadActivityType $type, ?User $actor, ?string $body = null, array $properties = []): LeadActivity
    {
        return $lead->activities()->create([
            'user_id' => $actor?->id,
            'type' => $type,
            'body' => $body,
            'properties' => array_filter($properties, fn ($value) => $value !== null) ?: null,
            'created_at' => now(),
        ]);
    }

    protected function syncNextFollowUp(Lead $lead): void
    {
        $next = $lead->followUps()->open()->min('due_at');

        $lead->forceFill(['next_follow_up_at' => $next ? Carbon::parse($next) : null, 'last_activity_at' => now()])->saveQuietly();
    }
}
