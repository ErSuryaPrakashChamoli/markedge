<?php

namespace App\Sales;

use App\Enums\ConversionEventType;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * One chronological view of a lead: capture, attribution touchpoints, stage moves, notes and
 * follow-ups. Read-only; every entry is derived from stored rows.
 */
class LeadTimeline
{
    /**
     * @return Collection<int, array{at: Carbon, kind: string, title: string, body: string|null, actor: string|null}>
     */
    public static function for(Lead $lead): Collection
    {
        $activities = $lead->activities()->with('user')->get();
        $userIds = $activities->flatMap(fn (LeadActivity $a) => [$a->properties['from'] ?? null, $a->properties['to'] ?? null, $a->properties['user_id'] ?? null])
            ->filter(fn ($id) => is_int($id))->unique()->values();
        $names = $userIds->isEmpty() ? collect() : User::query()->whereIn('id', $userIds)->pluck('name', 'id');

        $entries = collect([[
            'at' => $lead->created_at,
            'kind' => 'capture',
            'title' => 'Enquiry received'.($lead->form ? " via {$lead->form->name}" : ''),
            'body' => $lead->submitted_from_url ? "Submitted from {$lead->submitted_from_url}" : null,
            'actor' => null,
        ]]);

        foreach ($lead->events()->whereIn('type', [ConversionEventType::CtaClicked->value, ConversionEventType::LeadQualified->value, ConversionEventType::LeadConverted->value])->get() as $event) {
            $entries->push([
                'at' => $event->created_at,
                'kind' => 'analytics',
                'title' => $event->type->getLabel(),
                'body' => $event->path ? "Page {$event->path}" : null,
                'actor' => null,
            ]);
        }

        foreach ($activities as $activity) {
            $entries->push([
                'at' => $activity->created_at,
                'kind' => $activity->type->value,
                'title' => self::title($activity, $names),
                'body' => $activity->body,
                'actor' => $activity->user?->name,
            ]);
        }

        return $entries->sortByDesc(fn (array $entry) => $entry['at']->getTimestamp())->values();
    }

    /**
     * @param  Collection<int, string>  $names
     */
    protected static function title(LeadActivity $activity, Collection $names): string
    {
        $props = $activity->properties ?? [];

        return match ($activity->type) {
            LeadActivityType::StageChanged => sprintf('Moved from %s to %s%s',
                self::stageLabel($props['from'] ?? null), self::stageLabel($props['to'] ?? null),
                isset($props['reason']) ? ' ('.(config('markedge.sales.lost_reasons')[$props['reason']] ?? $props['reason']).')' : ''),
            LeadActivityType::Assigned => isset($props['to']) ? 'Assigned to '.($names[$props['to']] ?? "user #{$props['to']}") : 'Owner removed',
            LeadActivityType::FollowUpScheduled => ucfirst((string) ($props['type'] ?? 'task')).' scheduled for '.(isset($props['due_at']) ? Carbon::parse($props['due_at'])->format('d M Y H:i') : '—').(isset($props['user_id']) && isset($names[$props['user_id']]) ? " ({$names[$props['user_id']]})" : ''),
            LeadActivityType::FollowUpCompleted => ucfirst((string) ($props['type'] ?? 'task')).' completed',
            LeadActivityType::QualificationUpdated => 'Qualification updated'.(isset($props['fields']) && $props['fields'] !== [] ? ': '.implode(', ', $props['fields']) : ''),
            default => $activity->type->getLabel(),
        };
    }

    protected static function stageLabel(?string $value): string
    {
        return $value ? (LeadStatus::tryFrom($value)?->getLabel() ?? $value) : '—';
    }
}
