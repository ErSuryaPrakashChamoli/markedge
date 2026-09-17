<?php

namespace App\Automation;

use App\Enums\LeadActivityType;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\Channels\ChannelRegistry;
use App\Notifications\Channels\NotificationMessage;
use App\Sales\LeadWorkflow;
use Illuminate\Validation\ValidationException;

/**
 * Executes one validated action against a lead through the same services people use, so
 * timeline, notifications and analytics stay consistent. Returns a short factual result.
 */
class ActionRunner
{
    public function __construct(private readonly LeadWorkflow $workflow, private readonly ChannelRegistry $channels) {}

    /**
     * @param  array<string, mixed>  $action
     * @return array{action: string, outcome: string}
     */
    public function run(Lead $lead, array $action, string $idempotencyKey): array
    {
        $type = (string) $action['type'];

        $outcome = match ($type) {
            'assign_owner' => $this->assign($lead, (int) $action['user_id']),
            'set_priority' => $this->update($lead, ['priority' => LeadPriority::from((string) $action['priority'])]),
            'set_team' => $this->update($lead, ['team' => mb_substr((string) $action['team'], 0, 80)]),
            'move_stage' => $this->move($lead, LeadStatus::from((string) $action['status']), $action['reason'] ?? null),
            'add_note' => $this->note($lead, (string) $action['body']),
            'schedule_follow_up' => $this->followUp($lead, $action),
            'notify' => $this->notify($lead, $action, $idempotencyKey),
            default => 'unknown action',
        };

        return ['action' => $type, 'outcome' => $outcome];
    }

    protected function assign(Lead $lead, int $userId): string
    {
        $user = User::query()->where('is_active', true)->find($userId);

        if ($user === null) {
            return 'skipped: user not found or inactive';
        }

        if ((int) $lead->assigned_to === $user->id) {
            return 'unchanged';
        }

        $this->workflow->assign($lead, $user, null);

        return "assigned to user {$user->id}";
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function update(Lead $lead, array $attributes): string
    {
        $lead->fill($attributes);

        if (! $lead->isDirty()) {
            return 'unchanged';
        }

        $lead->save();

        return 'updated '.implode(',', array_keys($attributes));
    }

    protected function move(Lead $lead, LeadStatus $status, ?string $reason): string
    {
        if ($lead->status === $status) {
            return 'unchanged';
        }

        try {
            $this->workflow->transition($lead, $status, null, is_string($reason) ? $reason : null);
        } catch (ValidationException $exception) {
            return 'skipped: '.implode(' ', collect($exception->errors())->flatten()->all());
        }

        return "moved to {$status->value}";
    }

    protected function note(Lead $lead, string $body): string
    {
        $this->workflow->record($lead, LeadActivityType::Note, null, 'Automation: '.mb_substr($body, 0, 4000));

        return 'note added';
    }

    /**
     * @param  array<string, mixed>  $action
     */
    protected function followUp(Lead $lead, array $action): string
    {
        $owner = $lead->assignee ?? null;

        if ($owner === null && empty($action['user_id'])) {
            return 'skipped: lead has no owner';
        }

        $actor = $owner ?? User::query()->find((int) $action['user_id']);

        if ($actor === null) {
            return 'skipped: owner not found';
        }

        $this->workflow->scheduleFollowUp($lead, $actor, [
            'type' => $action['type_of_follow_up'] ?? 'task',
            'due_at' => now()->addHours(max(1, (int) $action['hours'])),
            'note' => isset($action['note']) ? 'Automation: '.$action['note'] : 'Scheduled by automation',
            'user_id' => $actor->id,
        ]);

        return 'follow-up scheduled';
    }

    /**
     * @param  array<string, mixed>  $action
     */
    protected function notify(Lead $lead, array $action, string $idempotencyKey): string
    {
        $recipients = $action['recipients'] ?? [];

        if (is_string($recipients)) {
            $recipients = array_map('trim', explode(',', $recipients));
        }

        if (($action['channel'] ?? null) === 'database') {
            $recipients = array_values(array_filter(array_map(fn ($r) => is_numeric($r) ? (int) $r : null, (array) $recipients)));

            if ($recipients === [] && $lead->assigned_to) {
                $recipients = [(int) $lead->assigned_to];
            }
        }

        $result = $this->channels->deliver((string) $action['channel'], new NotificationMessage(
            subject: $this->render((string) $action['subject'], $lead),
            body: $this->render((string) ($action['body'] ?? 'Enquiry #{id} ({name}) matched an automation rule.'), $lead),
            recipients: array_values((array) $recipients),
            url: LeadResource::getUrl('view', ['record' => $lead]),
            source: 'automation',
            idempotencyKey: $idempotencyKey.':notify',
            data: ['lead_id' => $lead->id, 'status' => $lead->status?->value],
        ));

        return "notify {$action['channel']}: {$result->status}".($result->reason ? " ({$result->reason})" : '');
    }

    /**
     * Placeholders limited to non-sensitive workflow fields.
     */
    protected function render(string $template, Lead $lead): string
    {
        return strtr($template, [
            '{id}' => (string) $lead->id,
            '{name}' => (string) $lead->name,
            '{company}' => (string) ($lead->company ?? ''),
            '{status}' => $lead->status?->getLabel() ?? '',
            '{priority}' => $lead->priority?->getLabel() ?? '',
            '{source}' => (string) ($lead->last_source ?? 'direct'),
        ]);
    }
}
